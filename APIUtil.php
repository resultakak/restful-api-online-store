<?php
class APIUtils
{
    //Converts query parameter sort='id,-name' to the format 'id asc, name desc' so that the string can be parsed in an 'order by' clause in SQL.
     public static function sortSerialize($string)
     {
        $sort='';
        $temp=explode(',',$string);
            
        for($i=0;$i<count($temp);$i++)
        {
            $temp2 = str_split($temp[$i]);
            if($temp2[0]=='-')
            {
                array_shift($temp2);
                $sort = ($i==count($temp)-1) ? $sort.implode($temp2).' desc' : $sort.implode($temp2).' desc,';
            }   
            else 
            {
                $sort = ($i==count($temp)-1) ? $sort.implode($temp2).' asc' : $sort.implode($temp2).' asc,';
            }           
        }
        return $sort;   
     }
     
    //Function to fetch the first single dimensional array in case it was fetched as a multidimensional array from JSON_DECODE(Because of [ ] enclosing the JSON array)
    public static function fetch_Single_Array($array)
    {
        $firstarray=Array();
        $flag=false;
        if(array_key_exists('0', $array))
        {
            $flag=true;
            $keys=array_keys($array[0]);
            for($i=0;$i<count($array[0]);$i++)
            {
                $firstarray[$keys[$i]]=$array[0][$keys[$i]];
            }
        }
        
        if($flag==true)
        {
        $array=$firstarray;
        }
        return $array;
    }
    
    
    //Function to clean inputs
    public static function sanitizeInputs($data) 
    {
        $sanitized_input = Array();
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $sanitized_input[$key] = APIUtils::sanitizeInputs($value);
            }
        } else {
            $sanitized_input = trim(strip_tags($data));
        }
        return $sanitized_input;
    }
    
    public static function isJson($string) 
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
     
}
?>