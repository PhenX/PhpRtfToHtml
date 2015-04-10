<?php abstract class RtfElement{protected function indentHtml($level){return str_repeat("&nbsp;",4*$level);}protected function indent($level){return str_repeat("\t",$level);}public function dumpHtml($level=0){return "<div>".$this->indentHtml($level).$this->dump($level)."</div>\n";}public function dump($level=0){return $this->indent($level)."ELEMENT UNKNOWN\n";}public function extractTextTree(){return null;}public function equals($object){return is_object($object)&&get_class($object)===get_class($this);}public function __toString(){return '';}public function __toRtf(){return '';}public function __toHtml(){return '';}public function free(){$this->__destruct();}public function __destruct(){}}class RtfHtml{private $output="";private $state=null;private $states=array();public function Format($root){$this->output="";$this->states=array();$this->state=new RtfState();$this->states[]=$this->state;$this->FormatGroup($root);return $this->output;}protected function FormatGroup($group){if($group->GetType()=="fonttbl")return;if($group->GetType()=="colortbl")return;if($group->GetType()=="stylesheet")return;if($group->GetType()=="info")return;if(substr($group->GetType(),0,4)=="pict")return;if($group->IsDestination())return;$this->state=clone $this->state;$this->states[]=$this->state;foreach($group->children as $child){if(get_class($child)=="RtfGroup")$this->FormatGroup($child);if(get_class($child)=="RtfControlWord")$this->FormatControlWord($child);if(get_class($child)=="RtfControlSymbol")$this->FormatControlSymbol($child);if(get_class($child)=="RtfText")$this->FormatText($child);}array_pop($this->states);$this->state=$this->states[count($this->states)-1];}protected function FormatControlWord($word){if($word->word=="plain")$this->state->Reset();if($word->word=="b")$this->state->bold=$word->parameter;if($word->word=="i")$this->state->italic=$word->parameter;if($word->word=="ul")$this->state->underline=$word->parameter;if($word->word=="ulnone")$this->state->end_underline=$word->parameter;if($word->word=="strike")$this->state->strike=$word->parameter;if($word->word=="v")$this->state->hidden=$word->parameter;if($word->word=="fs")$this->state->fontsize=ceil(($word->parameter / 24)* 16);if($word->word=="par")$this->output.="<p>";if($word->word=="lquote")$this->output.="&lsquo;";if($word->word=="rquote")$this->output.="&rsquo;";if($word->word=="ldblquote")$this->output.="&ldquo;";if($word->word=="rdblquote")$this->output.="&rdquo;";if($word->word=="emdash")$this->output.="&mdash;";if($word->word=="endash")$this->output.="&ndash;";if($word->word=="bullet")$this->output.="&bull;";if($word->word=="u")$this->output.="&loz;";}protected function BeginState(){$span="";if($this->state->bold)$span.="font-weight:bold;";if($this->state->italic)$span.="font-style:italic;";if($this->state->underline)$span.="text-decoration:underline;";if($this->state->end_underline)$span.="text-decoration:none;";if($this->state->strike)$span.="text-decoration:strikethrough;";if($this->state->hidden)$span.="display:none;";if($this->state->fontsize!=0)$span.="font-size:{$this->state->fontsize}px;";$this->output.="<span style='{$span}'>";}protected function EndState(){$this->output.="</span>";}protected function FormatControlSymbol($symbol){if($symbol->symbol=='\''){$this->BeginState();$this->output.=htmlentities(chr($symbol->parameter),ENT_QUOTES,'ISO-8859-1');$this->EndState();}}protected function FormatText($text){$this->BeginState();$this->output.=$text->text;$this->EndState();}}class Rtfparser{public $root=null;private $group=null;private $rtf=null;private $pos=0;private $len=0;private $char=null;protected function getChar(){$this->char=$this->rtf[$this->pos++];}protected function parseStartGroup(){$group=new RtfGroup();if($this->group!=null)$group->parent=$this->group;if($this->root==null){$this->group=$group;$this->root=$group;}else{$this->group->children[]=$group;$this->group=$group;}}protected function isLetter(){if(ord($this->char)>=65&&ord($this->char)<=90)return true;if(ord($this->char)>=90&&ord($this->char)<=122)return true;return false;}protected function isDigit(){if(ord($this->char)>=48&&ord($this->char)<=57)return true;return false;}protected function parseEndGroup(){$this->group=$this->group->parent;}protected function parseControlWord(){$this->getChar();$word="";while($this->isLetter()){$word.=$this->char;$this->getChar();}$parameter=null;$negative=false;if($this->char=='-'){$this->getChar();$negative=true;}while($this->isDigit()){if($parameter==null)$parameter=0;$parameter=$parameter * 10 + $this->char;$this->getChar();}if($parameter===null)$parameter=1;if($negative)$parameter=-$parameter;if($word=="u"){}else{if($this->char!=' ')$this->pos--;}$rtfword=new RtfControlWord();$rtfword->word=$word;$rtfword->parameter=$parameter;$this->group->children[]=$rtfword;}protected function parseControlSymbol(){$this->getChar();$symbol=$this->char;if($symbol=='\''){$this->getChar();$parameter=$this->char;$this->getChar();$parameter=$parameter.$this->char;$rtfsymbol=new RtfHexaControlSymbol();$rtfsymbol->setParameterFromHexa($parameter);$this->group->children[]=$rtfsymbol;}else{$rtfsymbol=new RtfControlSymbol();$rtfsymbol->symbol=$symbol;$this->group->children[]=$rtfsymbol;}}protected function parseControl(){$this->getChar();$this->pos--;if($this->isLetter())$this->parseControlWord();else$this->parseControlSymbol();}protected function parseText(){$text="";do{$terminate=false;$escape=false;if($this->char=='\\'){$this->getChar();switch($this->char){case '\\': $text.='\\';break;case '{': $text.='{';break;case '}': $text.='}';break;default:$this->pos=$this->pos - 2;$terminate=true;break;}}else if($this->char=='{'||$this->char=='}'){$this->pos--;$terminate=true;}if(!$terminate&&!$escape){$text.=$this->char;$this->getChar();}}while(!$terminate&&$this->pos<$this->len);$rtftext=new RtfText();$rtftext->text=$text;$this->group->children[]=$rtftext;}public function parse($rtf){$this->rtf=$rtf;$this->pos=0;$this->len=strlen($this->rtf);$this->group=null;$this->root=null;while($this->pos<$this->len){$this->getChar();if($this->char=="\n"||$this->char=="\r")continue;switch($this->char){case '{':$this->parseStartGroup();break;case '}':$this->parseEndGroup();break;case '\\':$this->parseControl();break;default:$this->parseText();break;}}}}class RtfState{public function __construct(){$this->Reset();}public function Reset(){$this->bold=false;$this->italic=false;$this->underline=false;$this->end_underline=false;$this->strike=false;$this->hidden=false;$this->fontsize=0;}}class RtfControlSymbol extends RtfElement{private static $_rtf_to_string=array('~'=>' ','|'=>'','-'=>'-','_'=>'-',':'=>'','*'=>'',);private static $_rtf_to_html=array('~'=>'&nbsp;','|'=>'|','-'=>'-','_'=>'-',':'=>'','*'=>'',);private $_symbol="";public function setSymbol($symbol){$this->_symbol=$symbol;}public function getSymbol(){return $this->_symbol;}public function dumpHtml($level=0){echo "<div style='color:blue'>";echo $this->indentHtml($level);echo "SYMBOL ".$this->__toHtml();echo "</div>\n";}public function extractTextTree(){return $this;}public function equals($object){return parent::equals($object)&&$this->_symbol===$object->_symbol;}public function __toString(){if(isset(self::$_rtf_to_string[$this->_symbol]))return self::$_rtf_to_string[$this->_symbol];return $this->_symbol;}public function __toRtf(){return '\\'.$this->_symbol;}public function __toHtml(){if(isset(self::$_rtf_to_html[$this->_symbol]))return self::$_rtf_to_html[$this->_symbol];return $this->_symbol;}public function __destruct(){parent::__destruct();$this->_symbol=null;}}class RtfControlWord extends RtfElement{public $word="";public $parameter=0;public function dumpHtml($level=0){echo "<div style='color:green'>";echo $this->indentHtml($level);echo "WORD{$this->word}({$this->parameter})";echo "</div>";}public function equals($object){return parent::equals($object)&&$this->word===$object->word&&$this->parameter===$object->parameter;}}class RtfGroup extends RtfElement{public $parent=null;public $children=array();public function GetType(){if(sizeof($this->children)==0)return null;$child=$this->children[0];if(get_class($child)!="RtfControlWord")return null;return $child->word;}public function IsDestination(){if(sizeof($this->children)==0)return null;$child=$this->children[0];if(get_class($child)!="RtfControlSymbol")return null;return $child->symbol=='*';}public function dumpHtml($level=0){echo "<div>";echo $this->indentHtml($level);echo "{";echo "</div>\n";foreach($this->children as $child){if(get_class($child)=="RtfGroup"){if($child->GetType()=="fonttbl")continue;if($child->GetType()=="colortbl")continue;if($child->GetType()=="stylesheet")continue;if($child->GetType()=="info")continue;if(substr($child->GetType(),0,4)=="pict")continue;if($child->IsDestination())continue;}$child->dumpHtml($level + 2);}echo "<div>";echo $this->indentHtml($level);echo "}";echo "</div>\n";}public function extractTextTree(){$root=new self();$root->parent=$this->parent;foreach($this->children as $child){if(get_class($child)=="RtfGroup"){if($child->GetType()=="fonttbl")continue;if($child->GetType()=="colortbl")continue;if($child->GetType()=="stylesheet")continue;if($child->GetType()=="info")continue;if(substr($child->GetType(),0,4)=="pict")continue;if($child->IsDestination())continue;}$subtree=$child->extractTextTree();if($subtree!==null){$root->children[]=$subtree;}}return(count($root->children)===0)? null : $root;}public function equals($object){if(!parent::equals($object))return false;if(count($this->children)!==count($object->children))return false;foreach($this->children as $i=>$child){if(!$child->equals($object->children[$i]))return false;}return true;}}class RtfHexaControlSymbol extends RtfControlSymbol{private $_value=0;public function getValue(){return $this->_value;}public function setValueFromHex($value){$this->_value=hexdec($value);}public function setValueFromDec($value){$this->_value=$value;}public function equals($object){return parent::equals($object)&&$this->_value===$object->_value;}public function __toString(){return html_entity_decode($this->__toHtml());}public function __toRtf(){return '\\'."'".dechex($this->_value);}public function __toHtml(){return '&#'.$this->_value.';';}public function __destruct(){parent::__destruct();$this->_value=null;}}class RtfText extends RtfElement{public $text;public function dumpHtml($level=0){echo "<div style='color:red'>";echo $this->indentHtml($level);echo "TEXT{$this->text}";echo "</div>";}public function extractTextTree(){if(trim($this->text)==="")return null;return $this;}public function equals($object){return parent::equals($object)&&$this->text===$object->text;}}