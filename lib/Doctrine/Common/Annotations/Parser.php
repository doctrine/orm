<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Annotations;

/**
 * A simple parser for docblock annotations.
 * 
 * @author Roman Borschel <roman@code-factory.org>
 */
class Parser
{
    /** Tags that are stripped prior to parsing in order to reduce parsing overhead. */
    private static $_strippedInlineTags = array(
        "{@example", "{@id", "{@internal", "{@inheritdoc",
        "{@link", "{@source", "{@toc", "{@tutorial", "*/"
    );
    
    private $_lexer;
    private $_isNestedAnnotation = false;
    private $_defaultAnnotationNamespace = '';
    private $_namespaceAliases = array();
    
    /**
     * Constructs a new AnnotationParser.
     */
    public function __construct()
    {
        $this->_lexer = new Lexer;
    }
    
    /**
     * Sets the default namespace that is assumed for an annotation that does not
     * define a namespace prefix.
     * 
     * @param $defaultNamespace
     */
    public function setDefaultAnnotationNamespace($defaultNamespace)
    {
        $this->_defaultAnnotationNamespace = $defaultNamespace;
    }
    
    /**
     * Sets an alias for an annotation namespace.
     * 
     * @param $namespace
     * @param $alias
     * @return unknown_type
     */
    public function setAnnotationNamespaceAlias($namespace, $alias)
    {
        $this->_namespaceAliases[$alias] = $namespace;
    }

    /**
     * Parses the given docblock string for annotations.
     * 
     * @param $docBlockString
     * @return array An array of Annotation instances. If no annotations are found, an empty
     * array is returned.
     */
    public function parse($docBlockString)
    {
        // Strip out some known inline tags.
        $input = str_replace(self::$_strippedInlineTags, '', $docBlockString);
        // Cut of the beginning of the input until the first '@'.
        $input = substr($input, strpos($input, '@'));
        
        $this->_lexer->reset();
        $this->_lexer->setInput(trim($input, '* /'));
        $this->_lexer->moveNext();
        
        if ($this->_lexer->isNextToken('@')) {
            return $this->Annotations();
        }
        return array();
    }

    /**
     * Attempts to match the given token with the current lookahead token.
     *
     * If they match, updates the lookahead token; otherwise raises a syntax
     * error.
     *
     * @param int|string token type or value
     * @return bool True, if tokens match; false otherwise.
     */
    public function match($token)
    {
        if (is_string($token)) {
            $isMatch = ($this->_lexer->lookahead['value'] === $token);
        } else {
            $isMatch = ($this->_lexer->lookahead['type'] === $token);
        }

        if ( ! $isMatch) {
            $this->syntaxError($token['value'], $this->_lexer->lookahead['value']);
        }

        $this->_lexer->moveNext();
    }
    
    /**
     * Raises a syntax error.
     * 
     * @param $expected
     * @throws Exception
     */
    private function syntaxError($expected, $got = "")
    {
        throw new \Exception("Expected: $expected. Got: $got");
    }
    
    /**
     * Annotations ::= Annotation ("*")* (Annotation)*
     */
    public function Annotations()
    {
        $this->_isNestedAnnotation = false;
        $annotations = array();
        $annot = $this->Annotation();
        if ($annot === false) {
            // Not a valid annotation. Go on to next annotation.
            $this->_lexer->skipUntil('@');
        } else {
            $annotations[get_class($annot)] = $annot;
        }
        
        while (true) {
            if ($this->_lexer->lookahead['value'] == '*') {
                $this->match('*');
            } else if ($this->_lexer->lookahead['value'] == '@') {
                $this->_isNestedAnnotation = false;
                $annot = $this->Annotation();
                if ($annot === false) {
                    // Not a valid annotation. Go on to next annotation.
                    $this->_lexer->skipUntil('@');
                } else {
                    $annotations[get_class($annot)] = $annot;
                }
            } else {
                break;
            }
        }
        
        return $annotations;
    }

    /**
     * Annotation ::= "@" AnnotationName [ "(" [Values] ")" ]
     * AnnotationName ::= SimpleName | QualifiedName
     * SimpleName ::= identifier
     * QualifiedName ::= NameSpacePart "\" (NameSpacePart "\")* SimpleName
     * NameSpacePart ::= identifier
     */
    public function Annotation()
    {
        $values = array();

        $nameParts = array();
        $this->match('@');
        $this->match(Lexer::T_IDENTIFIER);
        $nameParts[] = $this->_lexer->token['value'];
        while ($this->_lexer->isNextToken('\\')) {
            $this->match('\\');
            $this->match(Lexer::T_IDENTIFIER);
            $nameParts[] = $this->_lexer->token['value'];
        }

        if (count($nameParts) == 1) {
            $name = $this->_defaultAnnotationNamespace . $nameParts[0];
        } else {
            if (count($nameParts) == 2 && isset($this->_namespaceAliases[$nameParts[0]])) {
                $name = $this->_namespaceAliases[$nameParts[0]] . $nameParts[1];
            } else {
                $name = implode('\\', $nameParts);
            }
        }

        if ( ! $this->_isNestedAnnotation && $this->_lexer->lookahead != null
                && $this->_lexer->lookahead['value'] != '('
                && $this->_lexer->lookahead['value'] != '@'
                || ! is_subclass_of($name, 'Doctrine\Common\Annotations\Annotation')) {
            return false;
        }

        $this->_isNestedAnnotation = true; // Next will be nested

        if ($this->_lexer->isNextToken('(')) {
            $this->match('(');
            if ($this->_lexer->isNextToken(')')) {
                $this->match(')');
            } else {
                $values = $this->Values();
                $this->match(')');
            }
        }

        return new $name($values);
    }

    /**
     * Values ::= Value ("," Value)*
     */
    public function Values()
    {
        $values = array();

        $value = $this->Value();
        
        if ($this->_lexer->isNextToken(')')) {
            // Single value
            if (is_array($value)) {
                $k = key($value);
                $v = $value[$k];
                if (is_string($k)) {
                    // FieldAssignment
                    $values[$k] = $v;
                } else {
                    $values['value']= $value;
                }
            } else {
                $values['value'] = $value;
            }
            return $values;
        } else {
            // FieldAssignment
            $k = key($value);
            $v = $value[$k];
            $values[$k] = $v;
        }

        while ($this->_lexer->isNextToken(',')) {
            $this->match(',');
            $value = $this->Value();
            
            if ( ! is_array($value)) {
                $this->syntaxError('FieldAssignment', $value);
            }
            
            $k = key($value);
            $v = $value[$k];
            $values[$k] = $v;
        }

        return $values;
    }

    /**
     * Value ::= PlainValue | FieldAssignment
     */
    public function Value()
    {
        $peek = $this->_lexer->glimpse();
        if ($peek['value'] === '=') {
            return $this->FieldAssignment();
        }
        return $this->PlainValue();
    }

    /**
     * PlainValue ::= integer | string | float | Array | Annotation
     */
    public function PlainValue()
    {
        if ($this->_lexer->lookahead['value'] == '{') {
            return $this->Array_();
        }
        if ($this->_lexer->lookahead['value'] == '@') {
            return $this->Annotation();
        }

        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_STRING:
                $this->match(Lexer::T_STRING);
                return $this->_lexer->token['value'];
            case Lexer::T_INTEGER:
                $this->match(Lexer::T_INTEGER);
                return $this->_lexer->token['value'];
            case Lexer::T_FLOAT:
                $this->match(Lexer::T_FLOAT);
                return $this->_lexer->token['value'];
            case Lexer::T_TRUE:
                $this->match(Lexer::T_TRUE);
                return true;
            case Lexer::T_FALSE:
                $this->match(Lexer::T_FALSE);
                return false;
            default:
                var_dump($this->_lexer->lookahead);
                throw new \Exception("Invalid value.");
        }
    }

    /**
     * fieldAssignment ::= fieldName "=" plainValue
     * fieldName ::= identifier
     */
    public function FieldAssignment()
    {
        $this->match(Lexer::T_IDENTIFIER);
        $fieldName = $this->_lexer->token['value'];
        $this->match('=');
        return array($fieldName => $this->PlainValue());
    }

    /**
     * Array ::= "{" arrayEntry ("," arrayEntry)* "}"
     */
    public function Array_()
    {
        $this->match('{');
        $array = array();
        $this->ArrayEntry($array);

        while ($this->_lexer->isNextToken(',')) {
            $this->match(',');
            $this->ArrayEntry($array);
        }
        $this->match('}');

        return $array;
    }

    /**
     * ArrayEntry ::= Value | KeyValuePair
     * KeyValuePair ::= Key "=" Value
     * Key ::= string | integer
     */
    public function ArrayEntry(array &$array)
    {
        $peek = $this->_lexer->glimpse();
        if ($peek['value'] == '=') {
            if ($this->_lexer->lookahead['type'] === Lexer::T_INTEGER) {
                $this->match(Lexer::T_INTEGER);
            } else {
                $this->match(Lexer::T_STRING);
            }
            $key = $this->_lexer->token['value'];
            $this->match('=');
            return $array[$key] = $this->Value();
        } else {
            return $array[] = $this->Value();
        }
    }
}