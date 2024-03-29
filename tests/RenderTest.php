<?php
use Ailabph\AilabCore;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs;

class RenderTest extends TestCase
{
    protected function setUp(): void
    {
        AilabCore\Render::resetAll();
    }

    # ---------------------------------------------------------------------------------------------------------

    public function testTrue(){
        self::assertTrue(true);
    }

    public function xtestCreateObject(){
        $render = new AilabCore\Render();
        $this->assertIsObject($render,"Render Object");
    }

    /**
     * @throws Exception
     */
    public function xtestRenderSectionOnDefaultTemplateDirectory(){
        $page_content = AilabCore\Render::section("_page.twig");
        self::assertIsString($page_content,"page_content");
    }

    /**
     * @throws Exception
     */
    public function xtestRenderSectionOnCustomDirectory(){
        $test_dir_name = "test_dir_".AilabCore\Random::getRandomStr(5)."/tpl";
        vfs\vfsStream::setup($test_dir_name);
        $test_path = vfs\vfsStream::url($test_dir_name."/");
        $test_template_file = $test_path."test.twig";
        file_put_contents($test_template_file,"<h2>{{ test_title }}</h2>");

        $check_content = AilabCore\Render::section("test.twig",["test_title"=>"this is a title"],["template_path"=>$test_path]);
        self::assertEquals("<h2>this is a title</h2>",$check_content);
    }


    # SITE TITLE ---------------------------------------------------------------------------------------------------------
    public function xtestRenderSiteTitle(){
        AilabCore\Render::resetAll();
        AilabCore\Render::setSiteTitle("WEBSITE - HOME");
        $page_content = AilabCore\Render::page();
        self::assertStringContainsString("<title>WEBSITE - HOME</title>",$page_content);
    }


    # HEADER ---------------------------------------------------------------------------------------------------------

    /**
     * @throws Exception
     */
    public function xtestRenderGetHeaderCallableFunction(){
        AilabCore\Render::resetHeaderData();
        AilabCore\Render::addHeader("sampleHeaderFunction");
        $content = AilabCore\Render::getHeader();
        self::assertIsString($content);
        self::assertEquals("<h1>Header Content from a function</h1>",$content);
    }

    public function sampleMethodForHeader(): string{
        return "<h1>Header Content from a method</h1>";
    }

    /**
     * @throws Exception
     */
    public function xtestRenderGetHeaderCallableMethod(){
        AilabCore\Render::resetHeaderData();
        AilabCore\Render::addHeader("sampleMethodForHeader",$this);
        $content = AilabCore\Render::getHeader();
        self::assertEquals("<h1>Header Content from a method</h1>",$content);
    }

    public function xtestAddHeaderFunctionNotCallableThrowError(){
        AilabCore\Render::resetHeaderData();
        self::expectException(Exception::class);
        AilabCore\Render::addHeader("randomFunction");
    }

    public function xtestAddHeaderMethodNotCallableThrowError(){
        AilabCore\Render::resetHeaderData();
        self::expectException(Exception::class);
        AilabCore\Render::addHeader("randomMethod",$this);
    }


    # FOOTER ---------------------------------------------------------------------------------------------------------


    public function sampleMethodForFooter(): string{
        return "<script>console.log('footer content from a method');</script>";
    }

    /**
     * @throws Exception
     */
    public function xtestRenderGetFooterCallableFunction(){
        AilabCore\Render::resetFooterData();
        AilabCore\Render::addFooter("sampleFooterFunction");
        $content = AilabCore\Render::getFooter();
        self::assertIsString($content);
        self::assertEquals("<script>console.log('footer content from a function');</script>",$content);
    }

    /**
     * @throws Exception
     */
    public function xtestRenderGetFooterCallableMethod(){
        AilabCore\Render::resetFooterData();
        AilabCore\Render::addFooter("sampleMethodForFooter",$this);
        $content = AilabCore\Render::getFooter();
        self::assertEquals("<script>console.log('footer content from a method');</script>",$content);
    }


    # SECTION STACKS -------------------------------------------------------------------------------------------------

    /**
     * @throws Exception
     */
    public function xtestRenderAddContent(){
        AilabCore\Render::addContent("_content.twig",["content"=>"Content123"]);
        AilabCore\Render::addContent("_content.twig",["content"=>"Content456"]);
        $content = AilabCore\Render::getContent();
        self::assertIsString($content,"content");
        self::assertEquals("<div>Content123</div><div>Content456</div>",$content);
    }

    # PAGE ---------------------------------------------------------------------------------------------------------

    /**
     * @throws Exception
     */
    public function xtestRenderPageBasicParts(){
        $page_content = AilabCore\Render::page();
        self::assertIsString($page_content,"page_content");
        self::assertStringContainsString("<html",$page_content);
        self::assertStringContainsString("<head>",$page_content);
        self::assertStringContainsString("</head>",$page_content);
        self::assertStringContainsString("<body",$page_content);
        self::assertStringContainsString("</body>",$page_content);
        self::assertStringContainsString("</html>",$page_content);
    }

    /**
     * @throws Exception
     */
    public function xtestRenderPageAfterAddingContent(){
        AilabCore\Render::addContent("_content.twig",["content"=>"345"]);
        AilabCore\Render::addContent("_content.twig",["content"=>"678"]);
        $page_content = AilabCore\Render::page();
        self::assertIsString($page_content,"page_content");
        self::assertStringContainsString("<body",$page_content);
        self::assertStringContainsString("<div>345</div><div>678</div>",$page_content);
        self::assertStringContainsString("</body>",$page_content);
    }

    /**
     * @throws Exception
     */
    public function xtestRenderPageAfterAddingHeadContentFooter(){
        AilabCore\Render::addHeader("sampleHeaderFunction2");
        AilabCore\Render::addFooter("sampleFooterFunction");
        AilabCore\Render::addContent("_content.twig",["content"=>"this within the body is the content"]);
        $page_content = AilabCore\Render::page();
        self::assertStringContainsString("<style></style>", $page_content);
        self::assertStringContainsString("footer content from a function", $page_content);
        self::assertStringContainsString("this within the body is the content", $page_content);
    }

}

function sampleHeaderFunction(): string{
    return "<h1>Header Content from a function</h1>";
}

function sampleHeaderFunction2():string{
    return "<style></style>";
}

function sampleFooterFunction(): string{
    return "<script>console.log('footer content from a function');</script>";
}