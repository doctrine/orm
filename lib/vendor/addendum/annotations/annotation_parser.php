<?php
	/**
	 * Addendum PHP Reflection Annotations
	 * http://code.google.com/p/addendum/
	 *
	 * Copyright (C) 2006 Jan "johno Suchal <johno@jsmf.net>
	
	 * This library is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU Lesser General Public
	 * License as published by the Free Software Foundation; either
	 * version 2.1 of the License, or (at your option) any later version.
	 
	 * This library is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
	 * Lesser General Public License for more details.
	
	 * You should have received a copy of the GNU Lesser General Public
	 * License along with this library; if not, write to the Free Software
	 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
	**/
	
	class CompositeMatcher {
		protected $matchers = array();
		private $wasConstructed = false;

		public function add($matcher) {
			$this->matchers[] = $matcher;
		}

		public function matches($string, &$value) {
			if(!$this->wasConstructed) {
				$this->build();
				$this->wasConstructed = true;
			}
			return $this->match($string, $value);
		}

		protected function build() {}
	}

	class ParallelMatcher extends CompositeMatcher {
		protected function match($string, &$value) {
			$maxLength = false;
			$result = null;
			foreach($this->matchers as $matcher) {
				$length = $matcher->matches($string, $subvalue);
				if($maxLength === false || $length > $maxLength) {
					$maxLength = $length;
					$result = $subvalue;
				}
			}
			$value = $this->process($result);
			return $maxLength;
		}

		protected function process($value) {
			return $value;
		}
	}

	class SerialMatcher extends CompositeMatcher {
		protected function match($string, &$value) {
			$results = array();
			$total_length = 0;
			foreach($this->matchers as $matcher) {
				if(($length = $matcher->matches($string, $result)) === false) return false;
				$total_length += $length;
				$results[] = $result;
				$string = substr($string, $length);
			}
			$value = $this->process($results);
			return $total_length;
		}

		protected function process($results) {
			return implode('', $results);
		}
	}

	class SimpleSerialMatcher extends SerialMatcher {
		private $return_part_index;

		public function __construct($return_part_index = 0) {
			$this->return_part_index = $return_part_index;
		}

		public function process($parts) {
			return $parts[$this->return_part_index];
		}
	}

	class RegexMatcher {
		private $regex;

		public function __construct($regex) {
			$this->regex = $regex;
		}

		public function matches($string, &$value) {
			if(preg_match("/^{$this->regex}/", $string, $matches)) {
				$value = $this->process($matches);
				return strlen($matches[0]);
			}
			$value = false;
			return false;
		}

		protected function process($matches) {
			return $matches[0];
		}
	}
	
	class AnnotationsMatcher {
		public function matches($string, &$annotations) {
			$annotations = array();
			$annotation_matcher = new AnnotationMatcher;
			while(true) {
				if(preg_match('/\s(?=@)/', $string, $matches, PREG_OFFSET_CAPTURE)) {
					$offset = $matches[0][1] + 1;
					$string = substr($string, $offset);
				}  else {
					return; // no more annotations
				}
				if(($length = $annotation_matcher->matches($string, $data)) !== false) {
					$string = substr($string, $length);
					list($name, $params) = $data;
					$annotations[$name][] = $params;
				}
			}
		}
	}
	
	class AnnotationMatcher extends SerialMatcher {
		protected function build() {
			$this->add(new RegexMatcher('@'));
			$this->add(new RegexMatcher('[A-Z][a-zA-Z0-9_]+'));
			$this->add(new AnnotationParametersMatcher);
		}

		protected function process($results) {
			return array($results[1], $results[2]);
		}
	}

	class ConstantMatcher extends RegexMatcher {
		private $constant;

		public function __construct($regex, $constant) {
			parent::__construct($regex);
			$this->constant = $constant;
		}

		protected function process($matches) {
			return $this->constant;
		}
	}

	class AnnotationParametersMatcher extends ParallelMatcher {
		protected function build() {
			$this->add(new ConstantMatcher('', array()));
			$this->add(new ConstantMatcher('\(\)', array()));
			$params_matcher = new SimpleSerialMatcher(1);
			$params_matcher->add(new RegexMatcher('\(\s*'));
			$params_matcher->add(new AnnotationValuesMatcher);
			$params_matcher->add(new RegexMatcher('\s*\)'));
			$this->add($params_matcher);
		}
	}

	class AnnotationValuesMatcher extends ParallelMatcher {
		protected function build() {
			$this->add(new AnnotationTopValueMatcher);
			$this->add(new AnnotationHashMatcher);
		}
	}
	
	class AnnotationTopValueMatcher extends AnnotationValueMatcher {
		protected function process($value) {
			return array('value' => $value);
		}
	}

	class AnnotationValueMatcher extends ParallelMatcher {
		protected function build() {
			$this->add(new ConstantMatcher('true', true));
			$this->add(new ConstantMatcher('false', false));
			$this->add(new ConstantMatcher('TRUE', true));
			$this->add(new ConstantMatcher('FALSE', false));
			$this->add(new AnnotationStringMatcher);
			$this->add(new AnnotationNumberMatcher);
			$this->add(new AnnotationArrayMatcher);
		}
	}

	class AnnotationKeyMatcher extends ParallelMatcher {
		protected function build() {
			$this->add(new RegexMatcher('[a-zA-Z][a-zA-Z0-9_]*'));
			$this->add(new AnnotationStringMatcher);
			$this->add(new AnnotationIntegerMatcher);
		}
	}

	class AnnotationPairMatcher extends SerialMatcher {
		protected function build() {
			$this->add(new AnnotationKeyMatcher);
			$this->add(new RegexMatcher('\s*=\s*'));
			$this->add(new AnnotationValueMatcher);
		}

		protected function process($parts) {
			return array($parts[0] => $parts[2]);
		}
	}

	class AnnotationHashMatcher extends ParallelMatcher {
		protected function build() {
			$this->add(new AnnotationPairMatcher);
			$this->add(new AnnotationMorePairsMatcher);
		}
	}

	class AnnotationMorePairsMatcher extends SerialMatcher {
		protected function build() {
			$this->add(new AnnotationPairMatcher);
			$this->add(new RegexMatcher('\s*,\s*'));
			$this->add(new AnnotationHashMatcher);
		}

		protected function match($string, &$value) {
			$result = parent::match($string, $value);
			return $result;
		}

		public function process($parts) {
			return array_merge($parts[0], $parts[2]);
		}
	}

	class AnnotationArrayMatcher extends ParallelMatcher {
		protected function build() {
			$this->add(new ConstantMatcher('{}', array()));
			$values_matcher = new SimpleSerialMatcher(1);
			$values_matcher->add(new RegexMatcher('\s*{\s*'));
			$values_matcher->add(new AnnotationArrayValuesMatcher);
			$values_matcher->add(new RegexMatcher('\s*}\s*'));
			$this->add($values_matcher);
		}
	}

	class AnnotationArrayValuesMatcher extends ParallelMatcher {
		protected function build() {
			$this->add(new AnnotationArrayValueMatcher);
			$this->add(new AnnotationMoreValuesMatcher);
		}
	}

	class AnnotationMoreValuesMatcher extends SimpleSerialMatcher {
		protected function build() {
			$this->add(new AnnotationArrayValueMatcher);
			$this->add(new RegexMatcher('\s*,\s*'));
			$this->add(new AnnotationArrayValuesMatcher);
		}

		protected function match($string, &$value) {
			$result = parent::match($string, $value);
			return $result;
		}

		public function process($parts) {
			return array_merge($parts[0], $parts[2]);
		}
	}

	class AnnotationArrayValueMatcher extends ParallelMatcher {
		protected function build() {
			$this->add(new AnnotationValueInArrayMatcher);
			$this->add(new AnnotationPairMatcher);
		}
	}

	class AnnotationValueInArrayMatcher extends AnnotationValueMatcher {
		public function process($value) {
			return array($value);
		}
	}

	class AnnotationStringMatcher extends ParallelMatcher {
		protected function build() {
			$this->add(new AnnotationSingleQuotedStringMatcher);
			$this->add(new AnnotationDoubleQuotedStringMatcher);
		}
	}

	class AnnotationNumberMatcher extends RegexMatcher {
		public function __construct() {
			parent::__construct("-?[0-9]*\.?[0-9]*");
		}

		protected function process($matches) {
			$isFloat = strpos($matches[0], '.') !== false;
			return $isFloat ? (float) $matches[0] : (int) $matches[0];
		}
	}
	
	class AnnotationIntegerMatcher extends RegexMatcher {
		public function __construct() {
			parent::__construct("-?[0-9]*");
		}

		protected function process($matches) {
			return (int) $matches[0];
		}
	}

	class AnnotationSingleQuotedStringMatcher extends RegexMatcher {
		public function __construct() {
			parent::__construct("'([^']*)'");
		}

		protected function process($matches) {
			return $matches[1];
		}
	}

	class AnnotationDoubleQuotedStringMatcher extends RegexMatcher {
		public function __construct() {
			parent::__construct('"([^"]*)"');
		}

		protected function process($matches) {
			return $matches[1];
		}
	}
?>
