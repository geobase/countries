<?php

namespace Smart\Geo\Generator\Provider;

use DOMDocument;
use DOMElement;
use DOMText;
use DOMAttr;

class XmlParser
{
    /**
     * @param string|DOMElement $xml
     * @return object
     */
    public function parseXml($xml)
    {
        if (is_string($xml)) {
            $dom = new DOMDocument();
            $dom->loadXML($xml);
            $element = $dom;
        } else {
            $element = $xml;
        }

        $object = (object)[];
        if ($element->hasChildNodes()) {
            foreach ($element->childNodes as $child) {
                if (!$child instanceof DOMText && isset($child->tagName)) {
                    if (!isset($object->{$child->tagName})) {
                        $object->{$child->tagName} = [];
                    }
                    $object->{$child->tagName}[] = $this->parseXml($child);
                } elseif ($child instanceof DOMText) {
                    if (!isset($object->value)) {
                        $object->value = "";
                    }
                    $object->value .= $child->data;
                }
            }
        }

        if ($element->hasAttributes()) {
            $attributes = (object)[];
            foreach ($element->attributes as $attribute) {
                if ($attribute instanceof DOMAttr) {
                    $attributes->{$attribute->name} = (string)$attribute->value;
                }
            }
            $object->attributes = $attributes;
        }

        return $object;
    }
}
