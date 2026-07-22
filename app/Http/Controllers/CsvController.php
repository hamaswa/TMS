<?php

namespace App\Http\Controllers;

use App\Models\Cloth;
use League\Csv\Reader;
use League\Csv\Writer;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CsvController extends Controller
{
    public function importCsv(Request $request)
    {
        try {
            // Validate the incoming file
            $request->validate([
                'csvFile' => 'required|mimes:csv,txt', // Only CSV files allowed, max 2MB
            ]);

            // Handle file upload
            $file = $request->file('csvFile');
            $path = $file->getRealPath();

            // Open the CSV file
            $csv = Reader::createFromPath($path, 'r');
            $csv->setHeaderOffset(0); // Set header offset, if the file has headers
            $records = $csv->getRecords(); // Get records as an iterator

            // Loop through each record and insert it into the database
            foreach ($records as $record) {
                // Prepare customer data from the CSV columns
                $customerData = [
                    'name' => $record['نام'],
                    'phone_number1' => $record['رابطہ'],
                    'sleeve' => $record['کف'],
                    'shoulder' => $record['کندھا'],
                    'senaChorai' => $record['سینا چوڑائی'],
                    'shalwar' => $record['شلوار'],
                    'teraa' => $record['تیرہ'],
                    'jeab' => $record['جیپ'],
                    'length' => $record['لمبائی'],
                    'damanchorai' => $record['دامن چوڑائی'],
                    'button' => $record['بٹن'],
                    'shirtbutton' => $record['شرٹ کے بٹن'],
                    'swingtype' => $record['سیلائی'],
                    'arms' => $record['بازو'],
                    'chuta' => trim($record['چوتا'] ?? null),
                    'necktype' => $record['گلہ'],
                    'shalwarGheer' => $record['شلوار کا گھیر'],
                    'pancha' => $record['پنچہ'],
                    'Daaman' => $record['دامن'],
                    'plate_type' => $record['پلیٹ'],
                    'note' => $record['نوٹ'],
                    'comments' => $record['تبصرے'],
                    'user_id' => Auth::id(), // Assuming the user is the current logged-in user
                ];

                // dd($customerData);

                // Insert customer record into the database
                Customers::create($customerData);
            }

            // Return success response
            return redirect()->back()->with('insert', 'Customers data imported successfully.');
        } catch (\Exception $e) {
            // Catch any exceptions and return error message
            return redirect()->back()->with('error', 'Error importing CSV: ' . $e->getMessage());
        }
    }

    public function exportCsv()
    {
        try {
            $customers_list = Customers::select('name', 'phone_number1', 'sleeve', 'shoulder', 'senaChorai', 'shalwar', 'teraa', 'jeab', 'length', 'damanchorai', 'button', 'shirtbutton', 'swingtype', 'arms', 'Chuta', 'necktype', 'shalwarGheer', 'pancha', 'Daaman', 'plate_type', 'note', 'comments')->where('user_id', Auth::id())->get();


            // Define Urdu headers
            $headers = [
                'نام',
                'رابطہ',
                'کف',
                'کندھا',
                'سینا چوڑائی',
                'شلوار',
                'تیرہ',
                'جیپ',
                'لمبائی',
                'دامن چوڑائی',
                'بٹن',
                'شرٹ کے بٹن',
                'سیلائی',
                'بازو',
                'چوتا',
                'گلہ',
                'شلوار کا گھیر',
                'پنچہ',
                'دامن',
                'پلیٹ',
                'نوٹ',
                'تبصرے'
            ];


            // Convert data to array
            $data = $customers_list->map(function ($customer) {
                return $customer->toArray();
            })->toArray();

            // Create CSV
            $csv = Writer::createFromString('');
            $csv->insertOne($headers); // Add headers
            $csv->insertAll($data);    // Add data

            // Add UTF-8 BOM for proper encoding
            $csv_content = "\xEF\xBB\xBF" . (string) $csv;

            // Return CSV as a downloadable response
            $csv_name = 'customers_list.csv';
            return response($csv_content)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="' . $csv_name . '"');

            // return response()->json($customers_list);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function exportCsvCloths()
    {
        try {
            // Fetch the cloths data along with the related colors
            $cloths = Cloth::where('user_id', auth()->user()->id)
                ->with(['colors']) // Only load the colors, exclude images and videos
                ->latest()
                ->get();

            // Define the Urdu headers for the CSV
            $headers = [
                'کپڑے کی قسم',
                'کپڑے کی کمپنی',
                'کپڑے کا رنگ',
                'کپڑے کی لمبائی',
                'ریٹ فی میٹر',
                'کپڑے کی قیمت',
                'مجموعی قیمت'
            ];

            // Prepare data by mapping the cloths data and excluding unnecessary fields (like images and videos)
            $data = $cloths->map(function ($cloth) {
                return $cloth->colors->map(function ($color) use ($cloth) {
                    return [
                        'کپڑے کی قسم' => $cloth->type->name,
                        'کپڑے کی کمپنی' => $cloth->brand->name,
                        'کپڑے کا رنگ' => $color->color,
                        'کپڑے کی لمبائی' => $color->length . ' میٹر',
                        'ریٹ فی میٹر' => 'Rs:' . number_format($cloth->price),
                        'کپڑے کی قیمت' => 'Rs:' . number_format($cloth->price * $color->length),
                        'مجموعی قیمت' => 'Rs:' . number_format($cloth->price * $color->length),
                    ];
                });
            })->flatten(1)->toArray(); // Flatten the collection for CSV formatting

            // Create the CSV file
            $csv = Writer::createFromString('');
            $csv->insertOne($headers); // Insert the headers
            $csv->insertAll($data);    // Insert the data

            // Add UTF-8 BOM for proper encoding
            $csv_content = "\xEF\xBB\xBF" . (string) $csv;

            // Prepare the response to download the CSV
            $csv_name = 'cloths_list.csv';
            return response($csv_content)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="' . $csv_name . '"');
        } catch (\Throwable $th) {
            // Handle exceptions
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function importCsvCloths(Request $request)
    {
        try {
            // Validate the incoming file
            $request->validate([
                'csvFile' => 'required|mimes:csv,txt', // Only CSV files allowed, max 2MB
            ]);

            // Handle file upload
            $file = $request->file('csvFile');
            $path = $file->getRealPath();

            // Open the CSV file
            $csv = Reader::createFromPath($path, 'r');
            $csv->setHeaderOffset(0); // Set header offset, if the file has headers
            $records = $csv->getRecords(); // Get records as an iterator

            foreach ($records as $record) {
                $name = trim($record['کپڑے کی کمپنی']);
                $type_name = trim($record['کپڑے کی قسم']);
                $brand_slug = Str::slug($name);
                $type_slug = Str::slug($type_name);

                // Check if the brand already exists
                $existingBrand = ClothBrand::where('name', $name)->where('user_id', Auth::user()->id)->first();
                if (!$existingBrand) {
                    $cloth_brand = ClothBrand::create([
                        'name' => $name,
                        'brand_slug' => $brand_slug,
                        'user_id' => Auth::user()->id,
                    ]);
                } else {
                    $cloth_brand = $existingBrand;
                }

                // Check if the type already exists
                $existingType = ClothType::where('name', $type_name)->where('user_id', Auth::user()->id)->first();
                if (!$existingType) {
                    $cloth_type = ClothType::create([
                        'name' => $type_name,
                        'type_slug' => $type_slug,
                        'user_id' => Auth::user()->id,
                    ]);
                } else {
                    $cloth_type = $existingType;
                }

                // Check if the type already exists
                $existingcloth = Cloth::where('cloth_type_id', $cloth_type->id)->where('user_id', Auth::user()->id)->whereNull('deleted_at')->first(); //  deleted_at becasue soft delete is used

                // Get the price from the CSV record and trim any extra spaces
                $price = trim($record['ریٹ فی میٹر']);
                // dd($price);
                // Remove the 'Rs:' prefix from the price string if it exists
                $price = str_replace('Rs:', '', $price);
                // Save the cloth
                if (!$existingcloth) {

                    $cloth = Cloth::create([
                        'cloth_type_id' => $cloth_type->id,
                        'cloth_brand_id' => $cloth_brand->id,
                        'price' => (int)$price,
                        'user_id' => Auth::user()->id,
                    ]);
                }

                // Save colors and lengths
                $colors = trim($record['کپڑے کا رنگ']);
                $lengths = trim($record['کپڑے کی لمبائی']);

                $lengths = str_replace('میٹر', '', $lengths);

                // Check if the color already exists for the cloth
                $existingColor = ClothColor::where('cloth_id', $cloth->id)
                    ->where('color', $colors)
                    ->first();

                if (!$existingColor) {
                    ClothColor::create([
                        'cloth_id' => $cloth->id,
                        'color' => $colors,
                        'length' => $lengths,
                        'user_id' => Auth::user()->id,
                    ]);
                }
            }
            return redirect()->back()->with('insert', 'کپڑا کامیابی کے ساتھ شامل کیا گیا۔');
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }
}
