<?php

class XmlParserClass {

    private $dom;
    private $msgErro;

    public function __construct($xml) {
        
        $xml = mb_convert_encoding($xml, "UTF-8", "UTF-8,ISO-8859-1");
        $parser = xml_parser_create();
        
        if (!xml_parse($parser, $xml)) {
            $this->setMsgErro('Erro no XML: '.xml_get_error_code($parser). ' - '.xml_error_string(xml_get_error_code($parser)));
            return false;
        } else {
            $this->dom = new DOMDocument();
            $this->dom->loadXml($xml);
        }
    }
    
    public function getMsgErro() {
        return $this->msgErro;
    }
    
    public function setMsgErro($msg) {
        $this->msgErro = $msg;
    }

    public function getResult($node = null) {

        $result = $this->toArray($this->dom);
        if ($node) {
            if (isset($result[$node])) {
                return $result[$node];
            } else {
                $this->setMsgErro('Erro no XML - Índice indefinido: '.$node);
                return false;
            }
        } else {
            return $result;
        }
    }

    private function toArray($node) {
        
        $occurrence = array();
        $result = null;
        /** @var $node DOMNode */
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if (!isset($occurrence[$child->nodeName])) {
                    $occurrence[$child->nodeName] = null;
                }
                $occurrence[$child->nodeName]++;
            }
        }
        if (isset($child)) {
            if ($child->nodeName == '#text') {
                $result = html_entity_decode(
                    htmlentities($node->nodeValue, ENT_COMPAT, 'UTF-8'),
                    ENT_COMPAT,
                    'ISO-8859-15'
                );
            } else {
                if ($node->hasChildNodes()) {
                    $children = $node->childNodes;
                    for ($i = 0; $i < $children->length; $i++) {
                        $child = $children->item($i);
                        if ($child->nodeName != '#text') {
                            if ($occurrence[$child->nodeName] > 1) {
                                $result[$child->nodeName][] = $this->toArray($child);
                            } else {
                                $result[$child->nodeName] = $this->toArray($child);
                            }
                        } else {
                            if ($child->nodeName == '0') {
                                $text = $this->toArray($child);
                                if (trim($text) != '') {
                                    $result[$child->nodeName] = $this->toArray($child);
                                }
                            }
                        }
                    }
                }
                if ($node->hasAttributes()) {
                    $attributes = $node->attributes;
                    if (!is_null($attributes)) {
                        foreach ($attributes as $key => $attr) {
                            $result["@" . $attr->name] = $attr->value;
                        }
                    }
                }
            }

            // Trata array de retorno
            if (isset($result['errors']) and is_array($result['errors'])) {
                if (isset($result['errors']['error']['code'])) {
                    $firstError = $result['errors']['error'];
                    $result['errors']['error'] = Array(0 => $firstError);
                }
            }

            return $result;
        } else {
            return null;
        }
    }
    
}