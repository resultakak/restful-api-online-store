<?php
$request='products/1/categories/3?id=1&name=kanishk';

        $resource = rtrim(current(explode('?',$request)),'/');
		$temp = strpos($request,'?') ? explode('&',end(explode('?',$request))) : null;
		
        for($i=0;$i<count($temp);$i++)
		{
			$query_params[$i][0]=current(explode('=',$temp[$i]));
			$query_params[$i][1]=end(explode('=',$temp[$i]));
		}
        print_r($query_params);
?>