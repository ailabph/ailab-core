<?php

namespace Ailabph\AilabCore;

use Exception;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Render
{
    public string $template_path = "";

    /**
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        Tools::importValuesFromArrayToObject($options,$this);
    }


    /**
     * @throws Exception
     */
    static public function getRenderOptions(array $options): Render
    {
        return new self($options);
    }


    /**
     * @throws Exception
     */
    static public function page(): string{
        $pageParam = [];
        $pageParam["header_content"] = self::getHeader();
        $pageParam["content"] = self::getContent();
        $pageParam["footer_content"] = self::getFooter();
        return self::section("_page.twig", $pageParam);
    }

    /**
     * @throws Exception
     */
    static public function section(string $template_name, array $pageParam = [], array $options = []): string{
        Assert::isNotEmpty($template_name);
        $render_content = "";
        try{
            $template_path = self::getRenderOptions($options)->template_path;
            if(empty($template_path)){
                $template_path = Config::getBaseDirectory() . "/tpl";
            }

            $loader = new FilesystemLoader($template_path);
            $twig = new Environment($loader,["cache"=>false]);
            $render_content = $twig->render($template_name, $pageParam);
        }catch (Exception $e){
            Assert::throw("Unable to render $template_name, ".$e->getMessage());
        }
        return $render_content;
    }

    static public function resetAll(){
        self::$HEADER_DATA = [];
        self::$CONTENT_STACKS = [];
        self::$FOOTER_DATA = [];
    }



    # HEADER --------------------------------------------------------------------------------------------------

    static private array $HEADER_DATA = [];

    static public function resetHeaderData(){
        self::$HEADER_DATA = [];
    }

    /**
     * @param string $callable_function
     * @param object|null $from_object
     * @throws Exception
     */
    static public function addHeader(string $callable_function, $from_object = null){
        Assert::isNotEmpty($callable_function);
        self::$HEADER_DATA[] = $callable_function;
        if(is_object($from_object)){
            if(!is_callable([$from_object,$callable_function])){
                Assert::throw("method $callable_function is not callable");
            }
            self::$HEADER_DATA[] = $from_object;
        }
        else{
            if(!is_callable($callable_function)){
                Assert::throw("function $callable_function is not callable");
            }
        }
    }

    /**
     * @throws Exception
     */
    static public function getHeader(): string{
        if(count(self::$HEADER_DATA) == 0) return "";

        if(!is_string(self::$HEADER_DATA[0]))
            Assert::throw("first argument of header data must be a callable string");

        if(isset(self::$HEADER_DATA[1]) && !is_object(self::$HEADER_DATA[1]))
            Assert::throw("second argument for header data must be an object");

        if(isset(self::$HEADER_DATA[1])){
            return call_user_func([self::$HEADER_DATA[1], self::$HEADER_DATA[0]]);
        }

        return call_user_func(self::$HEADER_DATA[0]);
    }

    # FOOTER --------------------------------------------------------------------------------------------------

    static private array $FOOTER_DATA = [];

    static public function resetFooterData(){
        self::$FOOTER_DATA = [];
    }

    /**
     * @throws Exception
     */
    static public function addFooter(string $callable_function, $from_object = null){
        Assert::isNotEmpty($callable_function);
        self::$FOOTER_DATA[] = $callable_function;
        if(is_object($from_object)){
            self::$FOOTER_DATA[] = $from_object;
        }
    }

    /**
     * @throws Exception
     */
    static public function getFooter(): string{
        if(count(self::$FOOTER_DATA) == 0) return "";

        if(!is_string(self::$FOOTER_DATA[0]))
            Assert::throw("first argument of header data must be a callable string");

        if(isset(self::$FOOTER_DATA[1]) && !is_object(self::$FOOTER_DATA[1]))
            Assert::throw("second argument for header data must be an object");

        if(isset(self::$FOOTER_DATA[1])){
            return call_user_func([self::$FOOTER_DATA[1], self::$FOOTER_DATA[0]]);
        }

        return call_user_func(self::$FOOTER_DATA[0]);
    }


    # CONTENT STACKS ------------------------------------------------------------------------------------------------

    static private array $CONTENT_STACKS = [];

    static public function resetContentStacks(){
        self::$CONTENT_STACKS = [];
    }

    /**
     * @throws Exception
     */
    static public function addContent(string $template_name, array $contentParam = [], array $options =[]){
        self::$CONTENT_STACKS[] = self::section($template_name, $contentParam, $options);
    }

    static public function getContent(): string{
        return implode('', self::$CONTENT_STACKS);
    }
}