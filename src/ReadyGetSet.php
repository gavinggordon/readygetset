<?php

namespace GGG;

class ReadyGetSet
{

  private static $_result;
  private static $_attributes;
  private static $_language;
  private static $_prefix;

  private static function _getArgType( $v )
  {
    $arg = substr( gettype( $v ), 0, 1 );
    return ( static::$_language === 'php' ) ? '$' . $arg : $arg;
  }

  private static function _recurse( $array, $level = 0, $parent = '-' )
  {
    if(! is_array( $array ) )
    {
      return false;
    }
    foreach( $array as $key => $value )
    {
      if( is_array( $value ) && count( $value ) > 0 )
      {
        $parentKey = $parent . $key . '-';
        static::_recurse( $value, $level + 1, $parentKey );
      }
      else
      {
        $argType = static::_getArgType( $value );
        $arg = ( $argType !== NULL ) ? $argType : '';
        $ucKey = ucwords( $key );
        switch( static::$_language )
        {
          case 'js':
            $parentKeys = static::$_prefix . str_replace( '-', '.', $parent );
            static::$_result .= 'function set' . ucwords( $key ) . '(' . $arg . '){' . PHP_EOL . '  this.' . $parentKeys . $key . ' = ' . $arg . ';' . PHP_EOL . '};' . PHP_EOL;
            static::$_result .= 'function get' . ucwords( $key ) . '(){' . PHP_EOL . '  return this.' . $parentKeys . $key . ';' . PHP_EOL . '};' . PHP_EOL;
          break;

          case 'php':
            $parentKeys = static::$_prefix . str_replace( '-', '->', $parent );

/**
            if( is_string( static::$_prefix ) && strlen( static::$_prefix ) > 1 )
            {
              $indent = str_repeat( ' ', ( $level * 2) + 2 );
              if( $level === 0 )
              {
                $mainKey = static::$_prefix;
                static::$_attributes .=<<<EOA
{$indent}{$indent}'{$key}' => NULL,

EOA;

              }
              if( $level === 1 )
              {
                
                $mainKey = explode( '->', $parentKey );
                $mainKey = $mainKey[ count( $mainKey ) - 1 ];
                if(! empty( $mainKey ) )
                {
                  static::$_attributes .=<<<EOA
parent: {$parent}
parentKey: {$parentKey}
{$indent}'{$mainKey}' => [
{$indent}{$indent}'{$key}' => NULL,

EOA;

                }
                static::$_attributes .=<<<EOA
parent: {$parent}
parentKey: {$parentKey}
{$indent}{$indent}'{$key}' => NULL
{$indent}],

EOA;

              }
              if( $level === count( $array ) - 1 )
              {
                static::$_attributes .=<<<EOA

  ];
EOA;
              }
              $level++;
            }
            else
            {
              static::$_attributes .=<<<EOA
  private \${$key};

EOA;

            }
**/
            static::$_result .=<<<EOP

  // Set method for "{$key}" property
  public function set{$ucKey}({$arg}){
    \$this->{$parentKeys}{$key} = {$arg};
  }

  // Get method for "{$key}" property
  public function get{$ucKey}(){
    return \$this->{$parentKeys}{$key};
  }

EOP;

          break;
        }
      }
    }
    return true;
  }

  private static function _js( $arr )
  {
    if( static::_recurse( $arr ) )
    {
      return static::$_result;
    }
    return 'There was an error during the "_recurse" method, called within the "_makejs" method.';
  }

  private static function _php( $arr )
  {
    if( static::_recurse( $arr ) )
    {
      return [
        'head' => static::$_attributes,
        'body' => static::$_result
      ];
    }
    return 'There was an error during the "_recurse" method, called within the "_makephp" method.';
  }

  private static function wrapInPhpClass( $classname, $namespace, $content )
  {
    $phptag = html_entity_decode( '&lt;?php' );
    $namespace = ( is_null( $namespace ) ) ? NULL : "\nnamespace " . preg_replace( '#[\\\\]+#', '\\', $namespace );
    $namespace = ( $namespace === NULL ) ? '' : ( preg_match( '#[\\\\]+$#', $namespace ) ) ? preg_replace( '#[\\\\]+$#', ";\n", $namespace ) : $namespace . ";\n";
    $classname = ucwords( $classname );
    $tmpStr =<<<EOH
{$phptag}
{$namespace}
class {$classname}
{
  {$content['head']}
  public function __construct()
  {
    return \$this;
  }
  {$content['body']}
}
EOH;

    return $tmpStr;
  }

  public static function go( $outputLanguage, $associativeArray, $prefix = '', $writeToFile = false, $classname = NULL, $namespace = NULL )
  {
    static::$_result = '';
    static::$_attributes = ( is_string( $prefix ) && strlen( $prefix ) > 0 ) ? "\n" . '  private $' . $prefix . ' = [];' . "\n" : NULL;
    static::$_language = strtolower( $outputLanguage );
    static::$_prefix = $prefix;
    $result = 'Nothing has been created.';
    $staticMethod = '_' . strtolower( $outputLanguage );
    if( method_exists( 'ReadyGetSet', $staticMethod ) )
    {
       $result = static::{$staticMethod}( $associativeArray );
    }
    else
    {
      $result = 'Static method [' . $staticMethod . '] does not exist.';
    }
    if( $writeToFile === true )
    {
      if( $outputLanguage === 'php' && isset( $classname ) && is_string( $classname ) )
      {
        $result = static::wrapInPhpClass( $classname, $namespace, $result );
      }
      $filename = ( isset( $classname ) && is_string( $classname ) ) ? ucwords( $classname ) : ucwords( $prefix );
      $filename .= '.' . $outputLanguage;
      file_put_contents( $filename, $result );
    }
    else
    {
      echo $result;
    }
  }

}

/**
        $stats = [
          'name' => '',
          'avatar' => '',
          'level' => 0,
          'discipline' => '',
          'skills' => [
            'active' => [
              'attacks' => [],
              'spells' => []
            ],
            'passive' => [
              'offense' => [],
              'defense' => []
            ]
          ],
          'xp' => 0,
          'hp' => 50,
          'mp' => 0,
          'ap' => 0,
          'strength' => 0,
          'intelligence' => 0,
          'agility' => 0,
          'focus' => 0,
          'gold' => 0
        ];
**/

//ReadyGetSet::go( 'php', $stats, 'stats', true, 'Player', 'Test\\Namespace\\Fake\\\\' );

