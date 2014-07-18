<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 06/07/14.07.2014 19:03
 */

namespace Mindy\Helper;


use DOMDocument;
use Mindy\Base\Exception\Exception;

class Xml
{
    /**
     * Convert an Array to XML
     * @param string $rootNode
     * @param array $arr - aray to be converterd
     * @param string $version
     * @param string $encoding
     * @internal param string $node_name - name of the root node to be converted
     * @return string
     */
    public static function encode($rootNode = 'response', array $arr = [], $version = '1.0', $encoding = 'UTF-8')
    {
        $xml = new DomDocument($version, $encoding);
        $xml->appendChild(self::convert($xml, $rootNode, $arr));
        return $xml->saveXML();
    }

    /**
     * Convert an Array to XML
     * @param $xml
     * @param string $rootNode - name of the root node to be converted
     * @param array $data - array to be converted
     * @throws \Mindy\Base\Exception\Exception
     * @return \DOMNode
     */
    private static function convert($xml, $rootNode, $data)
    {
        $node = $xml->createElement($rootNode);

        if (is_array($data)) {
            // get the attributes first.;
            if (isset($data['@attributes'])) {
                foreach ($data['@attributes'] as $key => $value) {
                    if (!self::isValidTagName($key)) {
                        throw new Exception('[Array2XML] Illegal character in attribute name. attribute: ' . $key . ' in node: ' . $rootNode);
                    }
                    $node->setAttribute($key, self::bool2str($value));
                }
                unset($data['@attributes']); //remove the key from the array once done.
            }

            // check if it has a value stored in @value, if yes store the value and return
            // else check if its directly stored as string
            if (isset($data['@value'])) {
                $node->appendChild($xml->createTextNode(self::bool2str($data['@value'])));
                unset($data['@value']); //remove the key from the array once done.
                //return from recursion, as a note with value cannot have child nodes.
                return $node;
            } else if (isset($data['@cdata'])) {
                $node->appendChild($xml->createCDATASection(self::bool2str($data['@cdata'])));
                unset($data['@cdata']); //remove the key from the array once done.
                //return from recursion, as a note with cdata cannot have child nodes.
                return $node;
            }
        }

        //create subnodes using recursion
        if (is_array($data)) {
            // recurse to get the node for that key
            foreach ($data as $key => $value) {

                /*
                 * Check if the tag name or attribute name contains illegal characters
                 * Ref: http://www.w3.org/TR/xml/#sec-common-syn
                 */
                preg_match('/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i', $key, $matches);
                if ($matches[0] != $key) {
                    throw new Exception('[Array2XML] Illegal character in tag name. tag: ' . $key . ' in node: ' . $rootNode);
                }

                if (is_array($value) && is_numeric(key($value))) {
                    // MORE THAN ONE NODE OF ITS KIND;
                    // if the new array is numeric index, means it is array of nodes of the same kind
                    // it should follow the parent key name
                    foreach ($value as $k => $v) {
                        $node->appendChild(self::convert($xml, $key, $v));
                    }
                } else {
                    // ONLY ONE NODE OF ITS KIND
                    $node->appendChild(self::convert($xml, $key, $value));
                }
                unset($data[$key]); //remove the key from the array once done.
            }
        }

        // after we are done with all the keys in the array (if it is one)
        // we check if it has any text value, if yes, append it.
        if (!is_array($data)) {
            $node->appendChild($xml->createTextNode(self::bool2str($data)));
        }

        return $node;
    }

    /**
     * Get string representation of boolean value
     * @param $v
     * @return string
     */
    private static function bool2str($v)
    {
        if($v === true) {
            return 'true';
        } else if ($v === false) {
            return 'false';
        }

        return $v;
    }
}