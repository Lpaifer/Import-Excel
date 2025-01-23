<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // List Users
    public function index()
    {
        // Recovery the registers of DataBase
        $users = User::get();

        // Load the view
        return view('users.index', ['users' => $users]);
    }

    public function import(Request $request)
    {
        //Validate the file
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ],[
            'file.required' => 'The field file is required',
            'file.mimes' => 'File Invalited, need to send CSV file',
            'file.max' => 'File size greater than :max Mb'
        ]);

        //Create array with columns in database
        $headers = ['name', 'email', 'password'];

        // Receive file, read and convert the string to array
        $dataFile = array_map('str_getcsv', file($request->file('file')));

        // Count of quantity ids
        $numberRegisteredRecords = 0;

        // Verify Email
        $emailAlreadyRegistered = false;

        // Scroll through the lines of the csv file
        foreach ($dataFile as $keyData => $row) {
            
            //convert the line in array
            $values = explode(';', $row[0]);
            
            //Increment the register
            $numberRegisteredRecords++;    

            // Scroll through the colummns from header
            foreach ($headers as $key => $header) {
                 
                //Assign value to element from array
                $arrayValues[$keyData][$header] = $values[$key];
                
                // Verify if the column is email
                if($header == "email"){

                    // Verify if email already exist in database
                    if(User::where('email', $arrayValues[$keyData]['email']) -> first()){
                        $emailAlreadyRegistered .= $arrayValues[$keyData]['email'] . ", ";
                    }
                }
                //Verify the column is password
                if($header == "password"){

                    //encrypt the password
                    $arrayValues[$keyData][$header] = Hash::make(Str::random(7), ['rounds' => 12]);

                }

            } 
        }
        // Verify if exist email already register, return error and not register in database
        if($emailAlreadyRegistered){

            //Redirect the user for last page and send error message
            return back()->with('error', 'Data not imported. Exist email already registered <br>Quantity: ' . $emailAlreadyRegistered);
        }

        // Register records in the Database
        User:: insert($arrayValues);

        // Redirect for last page and send message to sucess
        return back()->with('sucess', 'Data imported with sucess. <br>Quantity: ' . $numberRegisteredRecords);
    }
}
