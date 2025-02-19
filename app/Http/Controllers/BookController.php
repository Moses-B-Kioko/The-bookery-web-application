<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SubGenre;
use App\Models\Book;
use App\Models\User;
use App\Models\Seller;
use App\Models\BookImage;
use App\Models\TempImage;
use App\Models\BookRating;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;


class BookController extends Controller
{
    public function index(Request $request)
{
    // Retrieve the seller ID of the currently authenticated user
    $sellerId = auth('seller')->id();

    // Initialize the query, filtering books by seller ID and eager loading book images
    $books = Book::where('seller_id', $sellerId)->latest('id')->with('book_images');

    // Filter by keyword if a search term is provided
    if ($request->get('keyword') != "") {
        $books = $books->where('title', 'like', '%'.$request->keyword.'%');
    }

    // Paginate the results
    $books = $books->paginate();

    // Prepare data for the view
    $data['books'] = $books;

    // Return the view with the books data
    return view('front.books.list', $data);
}

    

    public function adminIndex (Request $request) 
    {
        $books = Book::latest('id')->with('book_images');

        if($request->get('keyword') != "") {
            $books = $books->where('title', 'like', '%'.$request->keyword.'%');
        }
        $books = $books->paginate();
        $data['books'] = $books;
        return view('admin.books.list',$data);
    }

    public function create() {
        session(['url.intended' => url()->previous()]);

        $data = [];
        $categories = Category::orderBy('name','ASC')->get();
        $data['categories'] = $categories;
        return view('front.books.create', $data);
    }

    public function store(Request $request) {
        
        //dd($request->image_array);
        //exit();
        $rules = [
            'title' => 'required',
            'slug' => 'required',
            'author' => 'required',
            'price' => 'required|numeric',
            'description' => 'required',
            'track_qty' => 'required|in:Yes,No',
            'category' => 'required|numeric',
            'is_featured' => 'required|in:Yes,No',
            'condition' => 'required|in:Perfect,Good,Okay,Not That Okay,Bad',
        ];

        if (!empty($request->track_qty) && $request->track_qty == 'Yes') {
            $rules['qty'] = 'required|numeric';
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes()) {
            $book = new Book;
            $book->title = $request->title;
            $book->slug = $request->slug;
            $book->author = $request->author;
            $book->description = $request->description;
            $book->price = $request->price;
            $book->compare_price = $request->compare_price;
            $book->track_qty = $request->track_qty;
            $book->qty = $request->qty;
            $book->status = $request->status;
            $book->category_id = $request->category; 
            $book->sub_genre_id = $request->sub_category;
            $book->is_featured = $request->is_featured;
            $book->Condition = $request->condition;
            $book->shipping_returns = $request->shipping_returns;
            $book->short_description = $request->short_description;
            $book->related_books = (!empty($request->related_books)) ? implode(',',$request->related_books) : '';
            //$book->seller_id = auth()->guard('seller')->id();
            //$book->seller_id = auth()->id(); 
            // In your store method
            //$book->seller_id = $request->input('seller_id'); 
            $book->seller_id = auth('seller')->id();
            $book->save();


            //Save Gallery Pics
            if (!empty($request->image_array)) {
                foreach ($request->image_array as $temp_image_id) {

                    $tempImageInfo = TempImage::find($temp_image_id);
                    $extArray = explode('.',$tempImageInfo->name);
                    $ext = last($extArray); //like jpg,git,png etc


                    $bookImage = new BookImage();
                    $bookImage->book_id = $book->id;
                    $bookImage->image = 'NULL';
                    $bookImage->save();

                    $imageName = $book->id.'-'.$bookImage->id.'-'.time().'.'.$ext;
                    $bookImage->image = $imageName;
                    $bookImage->save();

                    //Generate Book Thumbnails

                    //Large Image
                    $sourcePath = public_path().'/temp/'.$tempImageInfo->name;
                    $destPath = public_path().'/uploads/book/large/'.$imageName;
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($sourcePath);
                    $image->scaleDown(1400);
                    $image->save($destPath);

                    //Small Image
                    $destPath = public_path().'/uploads/book/small/'.$imageName;

                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($sourcePath);
                    $image->cover(300,300);
                    $image->save($destPath);


                }
            }

            $request->session()->flash('success', 'Book added successfully');

            return response()->json([
                'status' => true,
                'message' => 'Book added successfully'
            ]);

            
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function edit ($id, Request $request) 
    {
        $book = Book::find($id);

        if(empty($book)) {
            return redirect()->route('books.index')->with('error','Books not found');
        }

        //Fetched Book Images
        $bookImages = BookImage::where('book_id',$book->id)->get();

        $subGenres = SubGenre::where('category_id',$book->category_id)->get();

        $relatedBooks = [];
        // Fetch related books
        if($book->related_books != '') {
            $bookArray = explode(',',$book->related_books);
            $relatedBooks = Book::whereIn('id',$bookArray)->with('book_images')->get();
        }

        $data = [];
        $categories = Category::orderBy('name','ASC')->get();
        $data['categories'] = $categories;
        $data['book'] = $book;
        $data['subGenres'] = $subGenres;
        $data['bookImages'] = $bookImages;
        $data['relatedBooks'] = $relatedBooks;


        return view('front.books.edit', $data);
    }

    public function update ($id, Request $request) {
        $book = Book::find($id);

        $rules = [
            'title' => 'required',
            'slug' => 'required',
            'author' => 'required',
            'price' => 'required|numeric',
            'description' => 'required',
            'track_qty' => 'required|in:Yes,No',
            'category' => 'required|numeric',
            'is_featured' => 'required|in:Yes,No',
            'condition' => 'required|in:Perfect,Good,Okay,Not That Okay,Bad',
        ];

        if (!empty($request->track_qty) && $request->track_qty == 'Yes') {
            $rules['qty'] = 'required|numeric';
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes()) {

            $book->title = $request->title;
            $book->slug = $request->slug;
            $book->author = $request->author;
            $book->description = $request->description;
            $book->price = $request->price;
            $book->compare_price = $request->compare_price;
            $book->track_qty = $request->track_qty;
            $book->qty = $request->qty;
            $book->status = $request->status;
            $book->category_id = $request->category; 
            $book->sub_genre_id = $request->sub_category;
            $book->is_featured = $request->is_featured;
            $book->Condition = $request->condition;
            $book->shipping_returns = $request->shipping_returns;
            $book->short_description = $request->short_description;
            $book->related_books = (!empty($request->related_books)) ? implode(',',$request->related_books) : '';
           
            $book->save();

            //Save Gallery Pics
            $request->session()->flash('success', 'Book updated successfully');

            return response()->json([
                'status' => true,
                'message' => 'Book updated successfully'
            ]);

            
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function destroy($id, Request $request) {
        $book = Book::find($id);

        if( empty($book)) {
            $request->session()->flash('error','Book not found');
            return response()->json([
                'status' => false,
                'notFound' => true
            ]);
        }
       
        $bookImages = BookImage::where('book_id',$id)->get();

        if(!empty($bookImages)) {
            foreach($bookImages as $bookImage) {
                File::delete(public_path('uploads/product/large/'.$bookImage->image));
                File::delete(public_path('uploads/product/small/'.$bookImage->image));
            }

            BookImage::where('book_id',$id)->delete();
        }

        $book->delete();

        $request->session()->flash('success','Book deleted successfully');

            return response()->json([
                'status' => true,
                'message' => "Book deleted successfully"
            ]);
    }

    public function getBooks(Request $request) {

        $tempBook = [];
        if($request->term != "") {
            $books = Book::where('title','like','%'.$request->term.'%')->get();

            if($books != null) {
                foreach ($books as $book) {
                    $tempBook[] = array('id' => $book->id, 'text' => $book->title);
                }
            }
        }

        return response()->json([
            'tags' => $tempBook,
            'status' => true
        ]);
    }

    public function bookRatings(Request $request) {
        $ratings = BookRating::select('book_ratings.*','books.title as bookTitle')->orderBy('book_ratings.created_at','DESC');
        $ratings = $ratings->leftJoin('books','books.id','book_ratings.book_id');

        if ($request->get('keyword') != "") {
            $ratings = $ratings->orwhere('books.title', 'like', '%'.$request->keyword.'%');
            $ratings = $ratings->orwhere('book_ratings.username', 'like', '%'.$request->keyword.'%');
        }
        $ratings = $ratings->paginate(10);

        return view('admin.books.ratings',[
            'ratings' => $ratings
        ]);
    }

    public function changeRatingStatus(Request $request) {

        $bookRating = BookRating::find($request->id);
        $bookRating->status = $request->status;
        $bookRating->save();

        session()->flash('success','Status changed successfully.');

        return response()->json([
            'status' => true
        ]);
    }

}
