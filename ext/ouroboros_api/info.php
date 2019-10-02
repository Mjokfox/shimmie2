<?php

/*
 * Name: Ouroboros API
 * Author: Diftraku <diftraku[at]derpy.me>
 * Description: Ouroboros-like API for Shimmie
 * Version: 0.2
 * Documentation:
 *
 */


class OuroborosAPIInfo extends ExtensionInfo
{
    public const KEY = "ouroboros_api";

    public $key = self::KEY;
    public $name = "Ouroboros API";
    public $authors = ["Diftraku"=>"diftraku[at]derpy.me"];
    public $description = "Ouroboros-like API for Shimmie";
    public $version = "0.2";
    public $documentation =
"Currently working features
 <ul>
   <li>Post:
     <ul>
       <li>Index/List</li>
       <li>Show</li>
       <li>Create</li>
     </ul>
   </li>
   <li>Tag:
     <ul>
       <li>Index/List</li>
     </ul>
   </li>
 </ul>
Tested to work with CartonBox using \"Danbooru 1.18.x\" as site type.
Does not work with Andbooru or Danbooru Gallery for reasons beyond me, took me a while to figure rating \"u\" is bad...
Lots of Ouroboros/Danbooru specific values use their defaults (or what I gathered them to be default)
and tons of stuff not supported directly in Shimmie is botched to work";
}