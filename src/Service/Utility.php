<?php
namespace ConfigurationValidator\Service;

class Utility {
    /**
     * Determine if the value represents a true/false value,
     * return True if it does, and set the $boolean value
     *
     * @param any $value
     * @param bool $boolean
     * @return bool
     */
    public static function getBoolean($value, &$boolean) {
        if(is_numeric($value)) {
            $boolean = ($value != 0);
            return true;
        }
        if(is_bool($value)) {
            $boolean = ($value == true);
            return true;
        }
        switch(strtolower($value)) {
            case "true":
            case "yes":
            case "y":
                $boolean = true;
                return true;
            case "false":
            case "no":
            case "n":
                $boolean = false;
                return true;
            default:
                return false;
        }
    }

    /**
     * Return true if the array is an associative (not ordinal)
     * array with items in it
     *
     * @param array $array
     * @return boolean
     */
    public static function isAssociativeArray(array $array) {
        $count = count($array);
        if($count > 0) {
            $i = 0;
            $ordinal = true;
            foreach($array as $key => $value) {
                if($key !== $i++) {
                    $ordinal = false;
                    break;
                }
            }
            return ! $ordinal;
        } else {
            return false;
        }
    }

    /**
     * Merge the second array into the first (like array_merge_recursive),
     * but don't automatically change non-array nodes to arrays - 
     * instead, overwrite them
     *
     * @param array $arr1
     * @param array $arr2
     * @return void
     */
    public static function array_merge_into(array &$arr1, array $arr2) {
        foreach($arr2 as $key => $value) {
            if(array_key_exists($key, $arr1)) {
                $isArray1 = is_array($arr1[$key]);
                $isArray2 = is_array($value);
                if($isArray1) {
                    if($isArray2) {
                        self::array_merge_into($arr1[$key], $value);
                    } else {
                        $arr1[$key][] = $value;
                    }
                } else {
                    $arr1[$key] = $value;
                }
            } else {
                $arr1[$key] = $value;
            }
        }
        return $arr1;
    }
    
}

