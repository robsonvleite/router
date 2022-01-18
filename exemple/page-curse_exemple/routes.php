<?php
namespace RoutesController;

class web
{
    public $cousers = [
        [
            "id" => 2101,
            "title" => "Learn HTML",
            "description" => "HTML is the standard markup language for Web pages. With HTML you can create your own Website."
        ],
        [
            "id" => 2102,
            "title" => "Learn CSS",
            "description" => "CSS is the language we use to style an HTML document. CSS describes how HTML elements should be displayed."
        ],
        [
            "id" => 2103,
            "title" => "Learn JavaScript",
            "description" => "JavaScript is the programming language of the Web."
        ],
        [
            "id" => 2104,
            "title" => "Learn PHP",
            "description" => "PHP is a server scripting language, and a powerful tool for making dynamic and interactive Web pages."
        ]
    ];

    public function __construct($router)
    {
        $this->router = $router;
    }
    
    // functions utils
    public function header(String $title): void
    {
        echo("<h1>$title - Couses123</h1>");
        echo("<div style='display: flex'>");
        echo("<div style='margin-right: 10px'><a href='". URL_BASE . "/'>Home</a></div>");
        echo("<div style='margin-right: 10px'><a href='". URL_BASE . "/courses'>Courses</a></div>");
        echo("<div style='margin-right: 10px'><a href='". URL_BASE . "/about'>About</a></div>");
        echo("<div style='margin-right: 10px'><a href='". URL_BASE . "/contact'>Contact</a></div>");
        echo("</div>");
        echo("<hr/>");
    }

    public function footer(): void
    {
        echo("<hr/>");
        echo("<div style='text-align: center'><span>Courses123 - Footer<span></div>");
    }

    public function getCourse($id)
    {
        foreach($this->cousers as $course) {
            if(array_key_exists('id', $course) && $course['id'] == $id) return $course;
        }
        return null;
    }
    // 

    public function home(): void
    {
        $this->header("Home");
        echo("<h2>Page Home</h2>");
        echo("<p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Aut doloribus esse ea est itaque enim tenetur vero nulla in ut eius qui, veritatis nam, nisi labore aliquam sequi aspernatur ducimus.</p>");
        echo("<p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Veniam exercitationem, vel at officia sed quos. In ea et vitae cupiditate a nobis excepturi! Illum porro eius iusto ducimus, deserunt dolorem.</p>");
        $this->footer();
    }

    public function about(): void
    {
        $this->header("About");
        echo("<h2>Page About</h2>");
        echo("<p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Aut doloribus esse ea est itaque enim tenetur vero nulla in ut eius qui, veritatis nam, nisi labore aliquam sequi aspernatur ducimus.</p>");
        echo("<p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Veniam exercitationem, vel at officia sed quos. In ea et vitae cupiditate a nobis excepturi! Illum porro eius iusto ducimus, deserunt dolorem.</p>");
        $this->footer();
    }

    public function contact(): void
    {
        $this->header("Contact");
        echo("<h2>Page Contact</h2>");
        echo("<p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Aut doloribus esse ea est itaque enim tenetur vero nulla in ut eius qui, veritatis nam, nisi labore aliquam sequi aspernatur ducimus.</p>");
        echo("<p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Veniam exercitationem, vel at officia sed quos. In ea et vitae cupiditate a nobis excepturi! Illum porro eius iusto ducimus, deserunt dolorem.</p>");
        $this->footer();
    }

    public function courses(): void
    {
        $this->header("Courses");
        echo("<h2>Page Courses</h2>");

        echo("<div style='margin-right: 10px'>COURSE: <a href='". URL_BASE . "/course/2101'>Course HTML</a></div>");
        echo("<div style='margin-right: 10px'>COURSE: <a href='". URL_BASE . "/course/2102'>Course CSS</a></div>");
        echo("<div style='margin-right: 10px'>COURSE: <a href='". URL_BASE . "/course/2103'>Course JavaScript</a></div>");
        echo("<div style='margin-right: 10px'>COURSE: <a href='". URL_BASE . "/course/2104'>Course PHP</a></div>");

        echo("<p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Aut doloribus esse ea est itaque enim tenetur vero nulla in ut eius qui, veritatis nam, nisi labore aliquam sequi aspernatur ducimus.</p>");
        echo("<p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Veniam exercitationem, vel at officia sed quos. In ea et vitae cupiditate a nobis excepturi! Illum porro eius iusto ducimus, deserunt dolorem.</p>");
        $this->footer();
    }

    public function course($req): void
    {
        $id = $req['id'];
        $course = $this->getCourse($id);
        
        $this->header("Courses");

        if($course !== null){
            echo("<h2>" . $course['title'] . "</h2>");
            echo "<p>". $course['description'] ."</p>";
            echo "<p>Lorem ipsum dolor sit amet consectetur adipisicing elit. A nihil ea molestiae, labore eligendi neque doloremque ab odit, harum officia deserunt, perspiciatis cupiditate pariatur. Eum harum mollitia dolor nam a.</p>";
        } else {
            header("Location: ". URL_BASE ."/error/404");
        }
        $this->footer();
    }

    public function http_404(): void
    {
        $this->header("Error 404");
        echo("<h2>Desculpe essa página não existe</h2>");
        echo("<p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Aut doloribus esse ea est itaque enim tenetur vero nulla in ut eius qui, veritatis nam, nisi labore aliquam sequi aspernatur ducimus.</p>");
        echo("<p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Veniam exercitationem, vel at officia sed quos. In ea et vitae cupiditate a nobis excepturi! Illum porro eius iusto ducimus, deserunt dolorem.</p>");
        $this->footer();
    }

    public function redirect(): void
    {
        $this->router->redirect("name.hello");
    }
}
