<?php
/*
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
namespace Doctrine\ORM\Tools\Code\Writer;

use Doctrine\ORM\Tools\Code\Writer;

/**
 * Default Doctrine's entity writer for automatic generation of code for entities classes
 * Actually it initialized the default Doctrine's templates
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Mykhailo Stadnyk <mikhus@gmail.com>
 */
class Entity extends Writer {

	/**
	 * Initializes Doctrine's default templates for entities classes generation
	 */
	public function init()
	{
		$this
			->setTemplate( 'class',
'<?php

<namespace>

<entityAnnotation>
<entityClassName>
{
<entityBody>
}'
			)->setTemplate( 'getMethod',
'/**
 * <description>
 *
 * @return <variableType>$<variableName>
 */
public function <methodName>()
{
<spaces>return $this-><fieldName>;
}'
			)->setTemplate( 'setMethod',
'/**
 * <description>
 *
 * @param <variableType>$<variableName>
 */
public function <methodName>(<methodTypeHint>$<variableName>)
{
<spaces>$this-><fieldName> = $<variableName>;
}'
			)->setTemplate( 'addMethod',
'/**
 * <description>
 *
 * @param <variableType>$<variableName>
 */
public function <methodName>(<methodTypeHint>$<variableName>)
{
<spaces>$this-><fieldName>[] = $<variableName>;
}'
			)->setTemplate( 'lifecycleCallbackMethod',
'/**
 * @<name>
 */
public function <methodName>()
{
<spaces>// Add your code here
}'
			)->setTemplate( 'constructorMethod',
'public function __construct()
{
<spaces><collections>
}
'
			);
	}

}
