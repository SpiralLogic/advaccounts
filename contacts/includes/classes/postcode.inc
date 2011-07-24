<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 11/07/11
 * Time: 4:29 PM
 * To change this template use File | Settings | File Templates.
 */

class postcode
{
private static $count=0;
    static function searchByPostcode($postcode = "*")
    {
        $sql = "SELECT ID as id, CONCAT(Locality,', ',Pcode,', ',State) as label,  CONCAT(Locality,'|',Pcode,'|',State) as value FROM postcodes WHERE Pcode LIKE " . db_escape($postcode . '%') . " ORDER BY Pcode LIMIT 20";
        $result = db_query($sql, "Could not find postcode");
        while (($resultArray[] = mysql_fetch_assoc($result)) || array_pop($resultArray)) ;
        return $resultArray;
    }

    static function searchByCity($city = "*")
    {
        $sql = "SELECT ID as id, CONCAT(Locality,', ',Pcode,', ',State) as label,  CONCAT(Locality,'|',Pcode,'|',State) as value FROM postcodes WHERE Locality LIKE " . db_escape('%' . $city . '%') . " ORDER BY Locality LIMIT 20";
        $result = db_query($sql, "Could not find city");
        while (($resultArray[] = mysql_fetch_assoc($result)) || array_pop($resultArray)) ;
        return $resultArray;
    }

    static function render($postcode, $city, $state,$options=array())
    {
        $opts = array('url'=>'search.php');
        extract(array_merge($opts,$options));
        $url='search.php';
        HTML::tr(true)->td(true)->label(array('content' => 'City: ','for'=>$city[0]),false)->td->td(true);
        UI::search($city[0], array('url' => $url.'?city=1', 'nodiv' => true, 'name' => $city[0], 'size' => 35, 'max' => 40, 'callback' => 'Adv.postcode'.static::$count.'.fetch'));
        HTML::_td()->tr;
        HTML::tr(true)->td(true)->label(array('content' => 'State: ','for'=>$state[0]),false)->td->td(true);
        HTML::input($state[0],array('maxlength'=>35,'size'=> 35,'value'=>$state[1],'name'=>$state[0]));
        HTML::tr(true)->td(true)->label(array('content' => 'Postcode: ','for'=>$postcode[0]),false)->td->td(true);

        UI::search($postcode[0], array('url' => $url.'?postcode=1', 'nodiv' => true, 'name' => $postcode[0], 'size' => 35, 'max' => 40, 'callback' => 'Adv.postcode'.static::$count.'.fetch'));
        HTML::_td()->tr;
        static::registerJS("#".$postcode[0],"#".$city[0],"#".$state[0]);
    } 

    static function registerJS($postcode, $city, $state)
    {
$count = static::$count;
        JS::beforeload(<<<JS
Adv.extend({
    postcode{$count}: (function() {
            var postcode = $('{$postcode}'),state = $('{$state}'), city = $('{$city}');
        return {
            fetch: function(data) {
                data = data.value.split('|');
                postcode.val(data[1]).trigger('change');
                state.val(data[2]).trigger('change');
                city.val(data[0]).trigger('change');
                return false;
            }
        }
    }())
})
JS
        );
        static::$count++;
    }
}
