<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Mail;

//Models
use App\Models\Category;
use App\Models\Article;
use App\Models\Page;
use App\Models\Contact;
use App\Models\Config;

class Homepage extends Controller
{
    public function __construct() {
        if(Config::find(1)->active == 0) {
            return redirect()->to('site-bakimda')->send();
        }
        view()->share('pages', Page::where('status', 1)->orderBy('order', 'ASC')->get());
        view()->share('categories', Category::where('status', 1)->get());
        //view()->share('categories', Category::where('status', 1)->inRandomOrder()->get());
    }

    public function index() {
        /*echo "<pre>";
        print_r(Category::all());*/
        $data['articles'] = Article::with('getCategory')->where('status', 1)->whereHas('getCategory', function($query){
            $query->where('status', 1);
        })->orderBy('created_at', 'DESC')->paginate(10);
        $data['articles']->withPath(url('sayfa'));
        return view('front.homepage', $data);
    }

    public function single($category, $slug) {
        $category = Category::whereSlug($category)->first() ?? abort(403,'Sanırım Kayboldunuz  kategori :(');
        $article = Article::whereSlug($slug)->whereCategoryId($category->id)->first() ?? abort(403,'Sanırım Kayboldunuz yazı :(');
        $article->increment('hit');
        $data['article'] = $article;
        return view('front.single', $data);
    }

    public function category($slug) {
        $category = Category::whereSlug($slug)->first() ?? abort(403,'Sanırım Kayboldunuz  kategori :(');
        $data['category'] = $category;
        $data['articles'] = Article::where('category_id', $category->id)->where('status', 1)->orderBy('created_at', 'DESC')->paginate(10);
        return view('front.category', $data);
    }

    public function page($slug) {
        $page = Page::whereSlug($slug)->first() ?? abort(403, 'Böyle bir sayfa bulunamadı.');
        $data['page'] = $page;
        return view('front.page', $data);
    }

    public function contact() {
        return view('front.contact');
    }

    public function contactpost(Request $request) {
        $rules = [
            'name' => 'required|min:5',
            'email' => 'required|email',
            'topic' => 'required',
            'message' => 'required|min:10',
        ];
        $validate = Validator::make($request->post(), $rules);
        if($validate->fails()) {
            return  redirect()->route('contact')->withErrors($validate)->withInput();
        }
        Mail::send([], [], function($message) use($request) {
            $message->from('iletisim@blogsitesi.com', 'Blog Sitesi');
            $message->to('furkan.0966@gmail.com');
            $message->setBody('Mesajı Gönderen: '.$request->name.'<br/>
                               Mesajı Gönderen Mail: '.$request->email.'<br/>
                               Mesaj Konusu: '.$request->topic.'<br/>
                               Mesaj: '.$request->message.'<br/><br/>
                               Mesaj Gönderilme Tarihi: '.date("d/m/Y h:i:sa").'', 'text/html');
            $message->subject($request->name.' den gelen mesaj..');
        });
        $contact = new Contact();
        $contact->name = $request->name;
        $contact->email = $request->email;
        $contact->topic = $request->topic;
        $contact->message = $request->message;
        $contact->save();
        /*
        echo "<pre>";
        print_r($request->post());
        */
        return redirect()->route('contact')->with('success', 'Mesajınızı aldım, teşekkürler :)');
    }
}
