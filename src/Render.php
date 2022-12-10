<?php

namespace Ailabph\AilabCore;

use Exception;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Render implements Loggable
{


    public string $template_path = "";

    /**
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        Tools::importValuesFromArrayToObject($options,$this);
    }


    #region UTILITIES -----------------------------------------------------------------------------------------------

    /**
     * @throws Exception
     */
    static function addLog(string|array|object $log, int $line)
    {
        Logger::add(msg:$log,category: "render", line:$line);
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
    static public function pureRender(string $twig, array $param = [], string $twig_path = ""):string{

        // fix template extension
        $twig = str_replace(".php",".twig",$twig);
        $twig = str_contains($twig,".twig") ? $twig : $twig.".twig";

        self::addLog("passed template_name: $twig",__LINE__);

        // dynamically check and identify template file path
        $template_found = false;
        if(!empty($twig_path)){
            $full_file_path = $twig_path . $twig;
            self::addLog("check path:$full_file_path",__LINE__);
            if(!file_exists($full_file_path)){
                Assert::throw("unable to locate template:$twig in path:$twig_path");
            }
            $template_found = true;
        }


        // check if file in (base)/v/
        if(!$template_found){
            $check_path = Config::getBaseDirectory()."/v/";
            $full_file_path = $check_path . $twig;
            self::addLog("check path:$full_file_path",__LINE__);
            if(file_exists($full_file_path)){
                self::addLog("file exist here",__LINE__);
                $template_found = true;
                $twig_path = $check_path;
            }
        }

        // check if inside (base)/tpl
        if(!$template_found){
            $check_path = Config::getBaseDirectory()."/tpl/";
            $full_file_path = $check_path . $twig;
            self::addLog("check path:$full_file_path",__LINE__);
            if(file_exists($full_file_path)){
                self::addLog("file exist here",__LINE__);
                $template_found = true;
                $twig_path = $check_path;
            }
        }

        // check if inside module/tpl
        if(!$template_found){
            $check_path = str_replace("src","tpl",__DIR__);
            $full_file_path = $check_path . $twig;
            self::addLog("check path:$full_file_path",__LINE__);
            if(file_exists($full_file_path)){
                self::addLog("file exist here",__LINE__);
                $template_found = true;
                $twig_path = $check_path;
            }
        }

        if(!$template_found){
            Assert::throw("Unable to locate template:$twig");
        }

        self::addLog("processed template_path: $twig_path",__LINE__);

        $loader = new FilesystemLoader($twig_path);
        $twig_env = new Environment($loader,["cache"=>false]);
        return $twig_env->render($twig, $param);
    }

    static public function resetAll(): void{
        self::resetSiteTitle();
        self::resetHeaderData();
        self::resetContentStacks();
        self::resetFooterData();
    }

    static public function getSiteWideParam(): array{
        $pageParam = [];
        $pageParam["SITE_TITLE"] = self::getSiteTitle();
        $pageParam["config"] = Config::getConfig();
        $pageParam["config_public"] = Config::getPublicConfig();
        return $pageParam;
    }

    static public function getContentFromScript(string $script_or_content): string{
        if(str_contains($script_or_content,".php")){
            $real_script_path = Config::getBaseDirectory() . $script_or_content;
            self::addLog("real_script_path:$real_script_path",__LINE__);
            if(file_exists($real_script_path)){
                ob_start();
                require_once($real_script_path);
                $content = ob_get_contents() ?? "";
                self::addLog("content from script:$content",__LINE__);
                ob_end_clean();
                return $content;
            }
        }
        return $script_or_content;
    }

    #endregion


    #region TITLE --------------------------------------------------------------------------------------------------

    static private string $SITE_TITLE = "";

    static public function resetSiteTitle(){
        self::$SITE_TITLE = "";
    }

    /**
     * @param string $site_title
     */
    static public function setSiteTitle(string $site_title){
        self::$SITE_TITLE = $site_title;
    }

    /**
     * @return string
     */
    static public function getSiteTitle(bool $with_dash = false): string{
        return ($with_dash?" - ":"") . self::$SITE_TITLE;
    }

    #endregion



    #region HEADER --------------------------------------------------------------------------------------------------

    static private array $HEADER_DATA = [];

    static public function resetHeaderData(){
        self::$HEADER_DATA = [];
    }

    static public function addHeader(string $script_or_content, bool $first_in_stack = false){
        $content = self::getContentFromScript($script_or_content);
        self::addLog("adding header content to stack:$content",__LINE__);
        if($first_in_stack){
            array_unshift(self::$HEADER_DATA,$content);
        }
        else{
            self::$HEADER_DATA[] = $content;
        }
    }

    static public function getHeader():string{
        return implode(PHP_EOL,self::$HEADER_DATA);
    }

    #endregion



    #region FOOTER --------------------------------------------------------------------------------------------------

    static private array $FOOTER_DATA = [];

    static public function resetFooterData(){
        self::$FOOTER_DATA = [];
    }

    static public function addFooter(string $script_or_content, bool $first_in_stack = false){
        $content = self::getContentFromScript($script_or_content);
        self::addLog("adding footer data to stack:$content",__LINE__);
        if($first_in_stack){
            array_unshift(self::$FOOTER_DATA,$content);
        }
        else{
            self::$FOOTER_DATA[] = $content;
        }
    }

    static public function getFooter():string{
        return implode(PHP_EOL,self::$FOOTER_DATA);
    }

    #endregion



    #region CONTENT STACKS -------------------------------------------------------------------------------------------

    static private array $CONTENT_STACKS = [];
    static private array $CONTENT_TOP_STACKS = [];
    static private array $CONTENT_BOTTOM_STACKS = [];

    static public function resetContentStacks(){
        self::$CONTENT_STACKS = [];
        self::$CONTENT_TOP_STACKS = [];
        self::$CONTENT_BOTTOM_STACKS = [];
    }

    static public function section(string $twig, array $param = [], array $options = []): string{
        Assert::isNotEmpty($twig);
        $render_content = "";
        try{
            $template_path = self::getRenderOptions($options)->template_path;
            $render_content = self::pureRender($twig, $param, $template_path);
        }catch (Exception $e){
            Assert::throw("Unable to render $twig, ".$e->getMessage());
        }
        return $render_content;
    }

    static public function addContent(string $twig, array $param = [], array $options =[], bool $first_in_stack = false){
        $content = self::section($twig, $param, $options);
        if($first_in_stack){
            array_unshift(self::$CONTENT_STACKS,$content);
        }
        else{
            self::$CONTENT_STACKS[] = $content;
        }

    }

    static public function getContent(): string{
        return implode(PHP_EOL, self::$CONTENT_STACKS);
    }

    static public function addTopContent(string $script_or_content, bool $first_in_stack = false): void{
        $content = self::getContentFromScript($script_or_content);
        self::addLog("adding top content to stack:$content",__LINE__);
        if($first_in_stack){
            array_unshift(self::$CONTENT_TOP_STACKS,$content);
        }
        else{
            self::$CONTENT_TOP_STACKS[] = $content;
        }
    }

    static public function getTopContent(): string{
        return implode(PHP_EOL,self::$CONTENT_TOP_STACKS);
    }

    static public function addBottomContent(string $script_or_content, bool $first_in_stack = false){
        $content = self::getContentFromScript($script_or_content);
        self::addLog("adding bottom content to stack:$content",__LINE__);
        if($first_in_stack){
            array_unshift(self::$CONTENT_BOTTOM_STACKS,$content);
        }
        else{
            self::$CONTENT_BOTTOM_STACKS[] = $content;
        }
    }

    static public function getBottomContent(): string{
        return implode(PHP_EOL,self::$CONTENT_BOTTOM_STACKS);
    }

    #endregion


    #region RENDER PAGE --------------------------------------------------------------------------------------

    static public function page(): string{
        self::addLog("rendering page...",__LINE__);
        $pageParam = self::getSiteWideParam();
        $pageParam["header_content"] = self::getHeader();
        $pageParam["top_content"] = self::getTopContent();
        $pageParam["content"] = self::getContent();
        $pageParam["bottom_content"] = self::getBottomContent();
        $pageParam["footer_content"] = self::getFooter();
        return self::section("_page.twig", $pageParam);
    }

    static public function addContentAndRenderPage(string $twig, array $param = []):string{
        self::addLog("adding content from $twig",__LINE__);
        self::addContent(twig:$twig,param:$param);
        return self::page();
    }

    static public function singlePage(string $twig, array $param = []): string{
        $pageParam = self::getSiteWideParam();
        $pageParam = array_merge($pageParam,$param);
        return self::pureRender($twig,$pageParam);
    }

    #endregion
}