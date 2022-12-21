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
        Tools::importValuesToObject($options,$this);
    }

    public static string $PAGE_NAME = "";
    public static string $PAGE_DETAILS = "";
    public static string $PAGE_DESCRIPTION = "";

    public static function getPage():string{
        $page_name = self::$PAGE_NAME;
        if(empty($page_name) && isset($GLOBALS["page"])){
            $page_name = $GLOBALS["page"];
        }
        return $page_name;
    }
    public static function getPageDetails():string{
        $page_details = self::$PAGE_DETAILS;
        if(empty($page_details) && isset($GLOBALS["page_details"])){
            $page_details = $GLOBALS["page_details"];
        }
        return $page_details;
    }
    public static function getPageDescription():string{
        $page_description = self::$PAGE_DESCRIPTION;
        if(empty($page_description) && isset($GLOBALS["page_description"])){
            $page_description = $GLOBALS["page_description"];
        }
        return $page_description;
    }

    #region UTILITIES -----------------------------------------------------------------------------------------------

    static function addLog(string|array|object $log, int $line)
    {
        Logger::add(msg:$log,category: "render", line:$line);
    }

    static public function getRenderOptions(array $options): Render
    {
        return new self($options);
    }

    static public function pureRender(string $twig,string $twig_path, array $param = [] ):string{

        // fix template extension
        $twig = str_replace(".php",".twig",$twig);
        $twig = str_replace("/","",$twig);
        $twig = str_contains($twig,".twig") ? $twig : $twig.".twig";
        self::addLog("passed template_name: $twig",__LINE__);
        $full_file_path = $twig_path . "/" . $twig;

        self::addLog("Rendering $full_file_path",__LINE__);

        if(!file_exists($full_file_path)) Assert::throw("template file:$full_file_path does not exist");

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
        $pageParam = Tools::getDefaultPageParam();
        $pageParam["SITE_TITLE"] = self::getSiteTitle();
        $pageParam["config"] = Config::getConfig();
        $pageParam["config_public"] = Config::getPublicConfig();
        return $pageParam;
    }

    static public function getContentFromScript(string $script_file): string
    {
        Assert::isPhpScriptAndExist(script_file:  $script_file,throw: true);
        self::addLog("executing script $script_file",__LINE__);
        $script_content = require_once($script_file);
        Assert::isNotEmpty($script_content,"script content from $script_file");
        self::addLog("script output:$script_file:$script_content",__LINE__);
        return $script_content;
    }

    #endregion


    #region TITLE --------------------------------------------------------------------------------------------------

    static private string $SITE_TITLE = "";

    static public function resetSiteTitle(){
        self::$SITE_TITLE = "";
    }

    static public function setSiteTitle(string $site_title){
        self::$SITE_TITLE = $site_title;
    }

    static public function getSiteTitle(bool $with_dash = false): string{
        return ($with_dash?" - ":"") . self::$SITE_TITLE;
    }

    #endregion


    #region HEADER --------------------------------------------------------------------------------------------------

    static private array $HEADER_DATA = [];

    static public function resetHeaderData(){
        self::$HEADER_DATA = [];
    }

    static public function addHeader(string $script_file,  bool $first_in_stack = false){
        Assert::isPhpScriptAndExist($script_file);
        if($first_in_stack){
            self::addLog("adding header content to first of stack:$script_file",__LINE__);
            array_unshift(self::$HEADER_DATA,$script_file);
        }
        else{
            self::addLog("adding header content to end of stack:$script_file",__LINE__);
            self::$HEADER_DATA[] = $script_file;
        }
    }

    static public function getHeader():string{
        $header_content = "";
        foreach (self::$HEADER_DATA as $script_file){
            $header_content .= self::getContentFromScript($script_file);
            $header_content .= PHP_EOL;
        }
        self::addLog("retrieved header:$header_content",__LINE__);
        return $header_content;
    }

    #endregion


    #region FOOTER --------------------------------------------------------------------------------------------------

    static private array $FOOTER_DATA = [];

    static public function resetFooterData(){
        self::$FOOTER_DATA = [];
    }

    static public function addFooter(string $script_file, bool $first_in_stack = false){
        Assert::isPhpScriptAndExist(script_file:$script_file,throw: true);
        if($first_in_stack){
            self::addLog("adding footer data to first of stack:$script_file",__LINE__);
            array_unshift(self::$FOOTER_DATA,$script_file);
        }
        else{
            self::addLog("adding footer data to end of stack:$script_file",__LINE__);
            self::$FOOTER_DATA[] = $script_file;
        }
    }

    static public function getFooter():string{
        $footer_content = "";
        foreach (self::$FOOTER_DATA as $script){
            $footer_content .= self::getContentFromScript($script);
            $footer_content .= PHP_EOL;
        }
        self::addLog("getting footer content:$footer_content",__LINE__);
        return $footer_content;
    }

    #endregion


    #region CONTENT STACKS -------------------------------------------------------------------------------------------

    static private array $CONTENT_STACKS = [];
    static private array $CONTENT_TOP_STACKS = [];
    static private array $CONTENT_BOTTOM_STACKS = [];

    public static bool $ENABLE_CONTENT_WRAPPER = false;
    static private string $CONTENT_WRAPPER_TWIG = "_content.twig";
    static private string $CONTENT_WRAPPER_TWIG_PATH = __DIR__ . "/tpl";
    static private array $CONTENT_WRAPPER_PARAM = [];

    static private string $BODY_WRAPPER_TWIG = "_body.twig";
    public static array $BODY_WRAPPER_PARAM = [];
    static private string $BODY_WRAPPER_TWIG_PATH = __DIR__."/tpl";
    private static string $BODY_WRAPPER_CALLBACK = "";

    static public function resetContentStacks(){
        self::$CONTENT_STACKS = [];
        self::$CONTENT_TOP_STACKS = [];
        self::$CONTENT_BOTTOM_STACKS = [];
        self::$CONTENT_WRAPPER_TWIG = "_content.twig";
        self::$CONTENT_WRAPPER_TWIG_PATH = __DIR__ . "/tpl";
        self::$CONTENT_WRAPPER_PARAM = [];
        self::$BODY_WRAPPER_TWIG = "_body.twig";
        self::$BODY_WRAPPER_TWIG_PATH = __DIR__."/tpl";
        self::$BODY_WRAPPER_PARAM = [];
    }

    static public function addBodyWrapper(string $twig, array $param = [], string $twig_path = ""){
        self::$BODY_WRAPPER_TWIG = $twig;
        self::$BODY_WRAPPER_PARAM = $param;
        if(!empty($twig_path)){
            self::$BODY_WRAPPER_TWIG_PATH = $twig_path;
        }
        $body_wrapper_twig_file = self::$BODY_WRAPPER_TWIG_PATH . "/" . self::$BODY_WRAPPER_TWIG;
        if(!file_exists($body_wrapper_twig_file)){
            Assert::throw("body wrapper file does not exist:$body_wrapper_twig_file");
        }
    }

    static public function addBodyWrapperCallback(string $script_file, string $twig_name, string $twig_path = ""): void{
        Assert::isPhpScriptAndExist(script_file: $script_file,throw: true);
        self::$BODY_WRAPPER_CALLBACK = $script_file;
        self::$BODY_WRAPPER_TWIG = $twig_name;
        self::$BODY_WRAPPER_TWIG_PATH = $twig_path;
    }

    /**
     * Must implement the following twig variables inside the twig file
     * - top_content|raw
     * - content|raw
     * - bottom_content|raw
     * @return string
     */
    static public function getBodyContent():string{
        if(!empty(self::$BODY_WRAPPER_CALLBACK)){
            self::addLog("executing script body callback:".self::$BODY_WRAPPER_CALLBACK,__LINE__);
            require_once(self::$BODY_WRAPPER_CALLBACK);
        }
        self::$BODY_WRAPPER_PARAM["page"] = self::$PAGE_NAME;
        self::$BODY_WRAPPER_PARAM["page_details"] = self::$PAGE_DETAILS;
        self::$BODY_WRAPPER_PARAM["top_content"] = self::getTopContent();
        self::$BODY_WRAPPER_PARAM["content"] = self::getContent();
        self::$BODY_WRAPPER_PARAM["bottom_content"] = self::getBottomContent();
        return self::pureRender(twig:self::$BODY_WRAPPER_TWIG, twig_path: self::$BODY_WRAPPER_TWIG_PATH,param: self::$BODY_WRAPPER_PARAM);
    }

    static public function addContentWrapper(string $twig, array $param = [], string $twig_path = ""){
        self::$ENABLE_CONTENT_WRAPPER = true;
        self::$CONTENT_WRAPPER_TWIG = $twig;
        self::$CONTENT_WRAPPER_PARAM = $param;
        if(!empty($twig_path)){
            self::$CONTENT_WRAPPER_TWIG_PATH = $twig_path;
        }
    }

    static public function section(string $twig, array $param = [], string $twig_path = ""): string{
        self::addLog("adding content section from template $twig",__LINE__);
        $section_content = self::pureRender(twig: $twig,twig_path: $twig_path,param: $param,);
        self::addLog("section content:$section_content",__LINE__);
        return $section_content;
    }

    static public function addContent(string $twig, array $param = [], bool $first_in_stack = false, string $twig_path = ""){
        $content = self::section(twig: $twig, param:$param,twig_path:$twig_path);
        if($first_in_stack){
            array_unshift(self::$CONTENT_STACKS,$content);
        }
        else{
            self::$CONTENT_STACKS[] = $content;
        }

    }

    static public function getContent(): string{
        $content = implode(PHP_EOL, self::$CONTENT_STACKS);
        if(self::$ENABLE_CONTENT_WRAPPER){
            self::$CONTENT_WRAPPER_PARAM["content"] = $content;
            if(self::$ENABLE_CONTENT_WRAPPER){
                return self::pureRender(twig:self::$CONTENT_WRAPPER_TWIG,twig_path:self::$CONTENT_WRAPPER_TWIG_PATH ,param:self::$CONTENT_WRAPPER_PARAM);
            }
        }
        return $content;
    }

    static public function addTopContent(string $script_file, bool $first_in_stack = false): void{
        Assert::isPhpScriptAndExist(script_file: $script_file,throw: true);
        if($first_in_stack){
            self::addLog("adding top content to first of stack:$script_file",__LINE__);
            array_unshift(self::$CONTENT_TOP_STACKS,$script_file);
        }
        else{
            self::addLog("adding top content to end of stack:$script_file",__LINE__);
            self::$CONTENT_TOP_STACKS[] = $script_file;
        }
    }

    static public function getTopContent(): string{
        $top_content = "";
        foreach (self::$CONTENT_TOP_STACKS as $script){
            $top_content .= self::getContentFromScript($script);
            $top_content .= PHP_EOL;
        }
        self::addLog("getting top content:$top_content",__LINE__);
        return $top_content;
    }

    static public function addBottomContent(string $script_file, bool $first_in_stack = false){
        Assert::isPhpScriptAndExist(script_file: $script_file,throw:true);
        if($first_in_stack){
            self::addLog("adding bottom content to first of stack:$script_file",__LINE__);
            array_unshift(self::$CONTENT_BOTTOM_STACKS,$script_file);
        }
        else{
            self::addLog("adding bottom content to end of stack:$script_file",__LINE__);
            self::$CONTENT_BOTTOM_STACKS[] = $script_file;
        }
    }

    static public function getBottomContent(): string{
        $bottom_content = "";
        foreach (self::$CONTENT_BOTTOM_STACKS as $content){
            $bottom_content .= self::getContentFromScript($content);
            $bottom_content .= PHP_EOL;
        }
        self::addLog("getting bottom content:$bottom_content",__LINE__);
        return $bottom_content;
    }

    #endregion


    #region RENDER PAGE --------------------------------------------------------------------------------------

    static public function page(): string{
        self::addLog("rendering page...",__LINE__);
        $pageParam = self::getSiteWideParam();
        $pageParam["header_content"] = self::getHeader();
        $pageParam["body_content"] = self::getBodyContent();
        $pageParam["footer_content"] = self::getFooter();
        $page_content = self::section(twig:"_page.twig", param:$pageParam,twig_path: self::getDefaultCoreTemplateDir());
        return self::pureRender(twig:"_final_page.twig",twig_path: self::getDefaultCoreTemplateDir(),param:["page_content"=>$page_content]);
    }

    static public function addContentAndRenderPage(string $twig, array $param = [], string $twig_path = ""):string{
        self::addLog("adding content from $twig",__LINE__);
        self::addContent(twig:$twig,param:$param,twig_path: $twig_path);
        return self::page();
    }

    static public function singlePage(string $twig, array $param = []): string{
        $pageParam = self::getSiteWideParam();
        $pageParam = array_merge($pageParam,$param);
        return self::pureRender(twig:$twig,twig_path: self::getDefaultCoreTemplateDir(),param:$pageParam);
    }

    #endregion


    #region PAGE LOGGER

    // message

    #endregion

    public static function getDefaultCoreTemplateDir():string{
        return Config::getBaseDirectory(of_core_module: true) . "/tpl";
    }
}