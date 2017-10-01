<?php

if (!function_exists('is_associative_array')) {

  function is_associative_array($array)
  {
      return is_array($array) && count(array_filter(array_keys($array), 'is_string')) > 0;
  }
}
