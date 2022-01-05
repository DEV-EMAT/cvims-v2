<?php


namespace App\Http\Controllers\API\Covid19Vaccine;



use App\Covid19Vaccine\PreRegistration;

use App\Covid19Vaccine\QualifiedPatient;

use App\Covid19Vaccine\VaccineCategory;

use App\Covid19Vaccine\Vaccinator;

use App\Covid19Vaccine\VaccinationMonitoring;

use App\Covid19Vaccine\VaccinationMonitoringSurvey;

use App\Http\Resources\QualifiedPatientResource;

use App\Http\Resources\PreRegResource;

use App\Covid19Vaccine\Survey;

use App\Covid19Vaccine\Barangay;

use App\Covid19Vaccine\Employer;

use App\Covid19Vaccine\Guardian;

use App\User;


use App\Http\Controllers\Controller;

use Illuminate\Http\Request;




use Auth;

use DB;

use Validator;

use Carbon\Carbon;

use Gate;

use Hash;


class PatientEncodingController extends Controller

{

    public $successStatus = 200;

    public $successCreateStatus = 201;

    public $errorStatus = 404;

    public $queryErrorStatus = 400;


    public function storePreRegistered(Request $request)

    {

        // $answer = [];

        // if($request['question2'] == "YES"){

        //     $answer['question3'] = 'required';

        // }if($request['question4'] == "YES"){

        //     $answer['question5'] = 'required';

        // }
       //

        $validate = Validator::make($request->all(), [

            'last_name' => 'required',

            'first_name' => 'required',

            'date_of_birth' => 'required',

            'sex' => 'required',

            'civil_status' => 'required',

            'contact_number' => 'required',

            'barangay_obj' => 'required',

            'specific_profession' => 'required',

            'categories' => 'required',

            'id_categories' => 'required',

            'home_address' => 'required',


        ]);


        if($validate->fails()){

            return response()->json(array('success' => false, 'messages' => 'May be missing required fields, Please check your input.', 'title'=> 'Oops! something went wrong.'));

        }else{

            $data = [

                'lastname' => convertData($request->last_name),

                'firstname' => convertData($request->first_name),

                'middlename' => convertData($request->middle_name),

                'dob' => convertData($request->date_of_birth),

                'suffix' => convertData($request->suffix),

            ];


            if(empty($this->validateUser($data))){


                DB::connection('covid19vaccine')->beginTransaction();

                try{


                    /* barangay */

                    $barangay = Barangay::findOrFail($request->barangay_obj['id']);


                    /* employer */

                    $employer = new Employer;

                    $employer->employment_status_id = convertData($request->employee_status['id']);

                    $employer->profession_id = convertData($request->profession['id']);

                    $employer->specific_profession = convertData($request->specific_profession);

                    $employer->employer_name = convertData($request->employer_name);

                    $employer->employer_contact = convertData($request->employer_contact);

                    $employer->employer_barangay_name = convertData($request->employer_address);

                    $employer->status = '1';

                    $employer->save();


                    $register = new PreRegistration();

                    $register->last_name = convertData($request->last_name);

                    $register->first_name = convertData($request->first_name);

                    $register->middle_name = (convertData($request->middle_name) == 'N/A')? 'NA' : convertData($request->middle_name);

                    $register->suffix = convertData($request->suffix);

                    $register->date_of_birth = convertData($request->date_of_birth);

                    $register->sex = convertData($request->sex);

                    $register->contact_number = "09" . convertData($request->contact_number);

                    $register->civil_status = convertData($request->civil_status);

                    $register->province = 'LAGUNA';

                    $register->city = 'CABUYAO';

                    $register->barangay = $barangay->barangay;

                    $register->barangay_id = convertData($request->barangay_obj['id']);

                    $register->home_address = convertData($request->home_address);

                    $register->employment_id = $employer->id;

                    $register->category_id = convertData($request->categories['id']);

                    $register->category_id_number = convertData($request->category_id_number);

                    $register->category_for_id = convertData($request->id_categories['id']);

                    $register->philhealth_number = convertData($request->philhealth_number);

                    $register->status = '1';

                    $register->save();


                    $current_date = Carbon::today();

                    $year = $current_date->year;

                    $day = $current_date->day;

                    $month = $current_date->month;

                    $register->registration_code = 'P' . chr(rand(65,90)) . chr(rand(65,90)) . chr(rand(65,90)) . str_pad($day . substr($year, -2) . $month . $register->id, 12, '0', STR_PAD_LEFT);

                    $register->save();


                    $register->image = 'covid19_vaccine_preregistration/default-avatar.png';

                    $register->save();


                    /* survey */

                    $survey = new Survey;

                    $survey->registration_id = $register->id;

                    $survey->question_1 = 'NO';

                    $survey->question_2 = ($request['withAlergy']) ? 'YES' : 'NO';

                    $survey->question_3 = ($request['withAlergy']) ? $request['withAlergyAnswer'] : null;

                    $survey->question_4 = ($request['withComorbidities']) ? 'YES' : 'NO';

                    $survey->question_5 = ($request['withComorbidities']) ? $request['withComorbiditiesAnswer'] : null;

                    $survey->question_6 = 'NO';

                    $survey->question_7 = null;

                    $survey->question_8 = null;

                    $survey->question_9 = 'YES';

                    $survey->question_10 = 'NO';

                    $survey->status = '1';

                    $survey->save();


                    if(!empty($request->gurdian_lname)){

                        $guardian = new Guardian;

                        $guardian->first_name = convertData($request->gurdian_lname);

                        $guardian->last_name = convertData($request->gurdian_fname);

                        $guardian->middle_name = (convertData($request->gurdian_mname) == 'N/A')? 'NA' : convertData($request->gurdian_mname);

                        $guardian->suffix = convertData($request->gurdian_suffix);

                        $guardian->contact_number = convertData($request->gurdian_contact_number);

                        $guardian->relationship = convertData($request->relationship);

                        $guardian->pre_registration_id = $register->id;

                        $guardian->status = '1';

                        $guardian->save();

                    }


                    $registeredPerson = PreRegistration::where('id', '=', $register->id)->where('status', '=', '1')->first();


                    $fullname = $registeredPerson->last_name;


                    if($registeredPerson->affiliation){

                        $fullname .= " " . $registeredPerson->affiliation;

                    }

                    $fullname .= ", " . $registeredPerson->first_name . " ";


                    if($registeredPerson->middle_name){

                        $fullname .= $registeredPerson->middle_name[0] . ".";

                    }


                    DB::connection('covid19vaccine')->commit();


                    return response()->json(array('success' => true, 'messages' => 'Thank you!', 'fullname' => $fullname, 'date_registered' => $registeredPerson->created_at->format('m-d-Y H:i:s'), 'registration_code' => $registeredPerson->registration_code));


                }catch(\PDOException $e){

                    DB::connection('covid19vaccine')->rollBack();

                    return response()->json(array('success' => false, 'messages' => 'Transaction Failed!','title' => 'Oops! something went wrong.'));

                }

            }else{

                return response()->json(array('success' => false, 'messages' => 'Please check your lastname, firstname, middlename and birthday!.','title' => 'Your name is already exist to our record!'));

            }


        }



    }


    public function storePreRegisteredOnline(Request $request)

    {

        // $answer = [];

        // if($request['question2'] == "YES"){

        //     $answer['question3'] = 'required';

        // }if($request['question4'] == "YES"){

        //     $answer['question5'] = 'required';

        // }


        $validate = Validator::make($request->all(), [

            'lastname' => 'required',

            'firstname' => 'required',

            'date_of_birth_formatted' => 'required',

            'sex_id' => 'required',

            'civil_status_id' => 'required',

            'contact_number' => 'required',

            'barangay_obj' => 'required',

            'categories' => 'required',

            'id_categories' => 'required',

            'home_address' => 'required',


        ]);


        if($validate->fails()){

            return response()->json(array('success' => false, 'messages' => 'May be missing required fields, Please check your input.', 'title'=> 'Oops! something went wrong.'));

        }else{

            $data = [

                'lastname' => convertData($request->lastname),

                'firstname' => convertData($request->firstname),

                'middlename' => convertData($request->middlename),

                'dob' => convertData($request->date_of_birth_formatted),

                'suffix' => convertData($request->suffix),

            ];


            if(empty($this->validateUser($data))){


                DB::connection('covid19vaccine')->beginTransaction();

                try{


                    /* barangay */

                    $barangay = Barangay::findOrFail($request->barangay_obj['id']);


                    /* employer */

                    $employer = new Employer;

                    $employer->employment_status_id = "5";

                    $employer->profession_id = "19";

                    $employer->specific_profession = "NA";

                    $employer->employer_name = "NA";

                    $employer->employer_contact = "NA";

                    $employer->employer_barangay_name = "NA";

                    $employer->status = '1';

                    $employer->save();


                    $register = new PreRegistration();

                    $register->last_name = convertData($request->lastname);

                    $register->first_name = convertData($request->firstname);

                    $register->middle_name = (convertData($request->middle_name) == 'N/A')? 'NA' : convertData($request->middlename);

                    $register->suffix = convertData($request->suffix);

                    $register->date_of_birth = convertData($request->date_of_birth_formatted);

                    $register->sex = convertData($request->sex_id);

                    $register->contact_number = convertData($request->contact_number);

                    $register->civil_status = convertData($request->civil_status_id);

                    $register->province = 'LAGUNA';

                    $register->city = 'CABUYAO';

                    $register->barangay = $barangay->barangay;

                    $register->barangay_id = convertData($request->barangay_obj['id']);

                    $register->home_address = convertData($request->home_address);

                    $register->employment_id = $employer->id;

                    $register->category_id = convertData($request->categories['id']);

                    $register->category_id_number = convertData($request->category_id_number);

                    $register->category_for_id = convertData($request->id_categories['id']);

                    $register->philhealth_number = convertData($request->philhealth_number);

                    $register->status = '1';

                    $register->save();


                    $current_date = Carbon::today();

                    $year = $current_date->year;

                    $day = $current_date->day;

                    $month = $current_date->month;

                    $register->registration_code = 'P' . chr(rand(65,90)) . chr(rand(65,90)) . chr(rand(65,90)) . str_pad($day . substr($year, -2) . $month . $register->id, 12, '0', STR_PAD_LEFT);

                    $register->save();


                    $register->image = 'covid19_vaccine_preregistration/default-avatar.png';

                    $register->save();


                    /* survey */

                    $survey = new Survey;

                    $survey->registration_id = $register->id;

                    $survey->question_1 = 'NO';

                    $survey->question_2 = ($request['withAllergy']) ? 'YES' : 'NO';

                    $survey->question_3 = ($request['withAllergy']) ? $request['allergiesAnswer'] : null;

                    $survey->question_4 = ($request['withComorbidities']) ? 'YES' : 'NO';

                    $survey->question_5 = ($request['withComorbidities']) ? $request['comorbiditiesAnswer'] : null;

                    $survey->question_6 = 'NO';

                    $survey->question_7 = null;

                    $survey->question_8 = null;

                    $survey->question_9 = 'YES';

                    $survey->question_10 = 'NO';

                    $survey->status = '1';

                    $survey->save();


                    $registeredPerson = PreRegistration::where('id', '=', $register->id)->where('status', '=', '1')->first();


                    $fullname = $registeredPerson->last_name;


                    if($registeredPerson->affiliation){

                        $fullname .= " " . $registeredPerson->affiliation;

                    }

                    $fullname .= ", " . $registeredPerson->first_name . " ";


                    if($registeredPerson->middle_name){

                        $fullname .= $registeredPerson->middle_name[0] . ".";

                    }


                    DB::connection('covid19vaccine')->commit();


                    return response()->json(array('success' => true, 'messages' => 'Thank you!', 'fullname' => $fullname, 'date_registered' => $registeredPerson->created_at->format('m-d-Y H:i:s'), 'registration_code' => $registeredPerson->registration_code));


                }catch(\PDOException $e){

                    DB::connection('covid19vaccine')->rollBack();

                    return response()->json(array('success' => false, 'messages' => 'Transaction Failed!','title' => 'Oops! something went wrong.'));

                }

            }else{

                return response()->json(array('success' => false, 'messages' => 'Please check your lastname, firstname, middlename and birthday!.','title' => 'Your name is already exist to our record!'));

            }


        }



    }


    public function storePreRegisteredMinorOnline(Request $request)

    {

        // $answer = [];

        // if($request['question2'] == "YES"){

        //     $answer['question3'] = 'required';

        // }if($request['question4'] == "YES"){

        //     $answer['question5'] = 'required';

        // }


        $validate = Validator::make($request->all(), [

            'lastname' => 'required',

            'firstname' => 'required',

            'date_of_birth_formatted' => 'required',

            'sex_id' => 'required',

            'civil_status_id' => 'required',

            'contact_number' => 'required',

            'barangay_obj' => 'required',

            'categories' => 'required',

            'id_categories' => 'required',

            'home_address' => 'required',

            'guardianLastName' => 'required',

            'guardianFirstName' => 'required',

            'guardianMiddleName' => 'required',

            'guardianSuffix' => 'required',

            'guardianContactNumber' => 'required',

            'relationship' => 'required',


        ]);


        if($validate->fails()){

            return response()->json(array('success' => false, 'messages' => 'May be missing required fields, Please check your input.', 'title'=> 'Oops! something went wrong.'));

        }else{

            $data = [

                'lastname' => convertData($request->lastname),

                'firstname' => convertData($request->firstname),

                'middlename' => convertData($request->middlename),

                'dob' => convertData($request->date_of_birth_formatted),

                'suffix' => convertData($request->suffix),

            ];


            if(empty($this->validateUser($data))){


                DB::connection('covid19vaccine')->beginTransaction();

                try{


                    /* barangay */

                    $barangay = Barangay::findOrFail($request->barangay_obj['id']);


                    /* employer */

                    $employer = new Employer;

                    $employer->employment_status_id = "5";

                    $employer->profession_id = "19";

                    $employer->specific_profession = "NA";

                    $employer->employer_name = "NA";

                    $employer->employer_contact = "NA";

                    $employer->employer_barangay_name = "NA";

                    $employer->status = '1';

                    $employer->save();


                    $register = new PreRegistration();

                    $register->last_name = convertData($request->lastname);

                    $register->first_name = convertData($request->firstname);

                    $register->middle_name = (convertData($request->middle_name) == 'N/A')? 'NA' : convertData($request->middlename);

                    $register->suffix = convertData($request->suffix);

                    $register->date_of_birth = convertData($request->date_of_birth_formatted);

                    $register->sex = convertData($request->sex_id);

                    $register->contact_number = convertData($request->contact_number);

                    $register->civil_status = convertData($request->civil_status_id);

                    $register->province = 'LAGUNA';

                    $register->city = 'CABUYAO';

                    $register->barangay = $barangay->barangay;

                    $register->barangay_id = convertData($request->barangay_obj['id']);

                    $register->home_address = convertData($request->home_address);

                    $register->employment_id = $employer->id;

                    $register->category_id = convertData($request->categories['id']);

                    $register->category_id_number = convertData($request->category_id_number);

                    $register->category_for_id = convertData($request->id_categories['id']);

                    $register->philhealth_number = convertData($request->philhealth_number);

                    $register->status = '1';

                    $register->save();


                    $current_date = Carbon::today();

                    $year = $current_date->year;

                    $day = $current_date->day;

                    $month = $current_date->month;

                    $register->registration_code = 'P' . chr(rand(65,90)) . chr(rand(65,90)) . chr(rand(65,90)) . str_pad($day . substr($year, -2) . $month . $register->id, 12, '0', STR_PAD_LEFT);

                    $register->save();


                    $register->image = 'covid19_vaccine_preregistration/default-avatar.png';

                    $register->save();


                    /* survey */

                    $survey = new Survey;

                    $survey->registration_id = $register->id;

                    $survey->question_1 = 'NO';

                    $survey->question_2 = ($request['withAllergyMinor']) ? 'YES' : 'NO';

                    $survey->question_3 = ($request['withAllergyMinor']) ? $request['allergiesAnswer'] : null;

                    $survey->question_4 = ($request['withComorbiditiesMinor']) ? 'YES' : 'NO';

                    $survey->question_5 = ($request['withComorbiditiesMinor']) ? $request['comorbiditiesAnswer'] : null;

                    $survey->question_6 = 'NO';

                    $survey->question_7 = null;

                    $survey->question_8 = null;

                    $survey->question_9 = 'YES';

                    $survey->question_10 = 'NO';

                    $survey->status = '1';

                    $survey->save();


                    $guardian = new Guardian;

                    $guardian->first_name = convertData($request->guardianFirstName);

                    $guardian->last_name = convertData($request->guardianLastName);

                    $guardian->middle_name = (convertData($request->guardianMiddleName) == 'N/A')? 'NA' : convertData($request->guardianMiddleName);

                    $guardian->suffix = convertData($request->guardianSuffix);

                    $guardian->contact_number = convertData($request->guardianContactNumber);

                    $guardian->relationship = convertData($request->relationship);

                    $guardian->pre_registration_id = $register->id;

                    $guardian->status = '1';

                    $guardian->save();


                    $registeredPerson = PreRegistration::where('id', '=', $register->id)->where('status', '=', '1')->first();


                    $fullname = $registeredPerson->last_name;


                    if($registeredPerson->affiliation){

                        $fullname .= " " . $registeredPerson->affiliation;

                    }

                    $fullname .= ", " . $registeredPerson->first_name . " ";


                    if($registeredPerson->middle_name){

                        $fullname .= $registeredPerson->middle_name[0] . ".";

                    }


                    DB::connection('covid19vaccine')->commit();


                    return response()->json(array('success' => true, 'messages' => 'Thank you!', 'fullname' => $fullname, 'date_registered' => $registeredPerson->created_at->format('m-d-Y H:i:s'), 'registration_code' => $registeredPerson->registration_code));


                }catch(\PDOException $e){

                    DB::connection('covid19vaccine')->rollBack();

                    return response()->json(array('success' => false, 'messages' => 'Transaction Failed!','title' => 'Oops! something went wrong.'));

                }

            }else{

                return response()->json(array('success' => false, 'messages' => 'Please check your lastname, firstname, middlename and birthday!.','title' => 'Your name is already exist to our record!'));

            }


        }



    }


    public function findRegisteredUser(Request $request){


        // $sex = '';

        // if(convertData($request['sex']) == "MALE"){

        //     $sex = '01_MALE';

        // }else{

        //     $sex = '02_FEMALE';

        // }


        $result = PreRegistration::where('last_name', '=', convertData($request['lastname']))

                ->where('first_name', '=', convertData($request['firstname']))

                ->where('middle_name', '=', convertData($request['middlename']))

                ->where('suffix', '=', convertData($request['suffix']))

                // ->where('date_of_birth', '=', $this->convertDate($request['date_of_birth']))

                ->where('date_of_birth', '=', date("m/d/Y", strtotime(convertData($request['date_of_birth']))))

                // ->where('sex', '=', $sex)

                ->select('id','last_name', 'first_name', 'middle_name', 'suffix', 'registration_code', 'created_at')

                ->first();


        if(!empty($result)){

            $fullname = $result->last_name;

            $fullname = (!empty($result->suffix) && $result->suffix != 'NA' && $result->suffix != 'N/A')? $fullname .' '. $result->suffix . ', ' : $fullname .', ';

            $fullname = $fullname . $result->first_name .' '. $result->middle_name;


            if(empty($result->registration_code)){

                $current_date = Carbon::today();

                $year = $current_date->year;

                $day = $current_date->day;

                $month = $current_date->month;


                $result->registration_code = 'P' . chr(rand(65,90)) . chr(rand(65,90)) . chr(rand(65,90)) . str_pad($day . substr($year, -2) . $month . $result->id, 12, '0', STR_PAD_LEFT);

                $result->save();

            }


            return response()->json(array('success' => true, 'message' => '', 'data' => array('fullname' => $fullname, 'date_registered' => $result->created_at->format('F d, Y  H:i A'), 'registration_code' => $result->registration_code)));

        }else{

            return response()->json(array('success' => false, 'messages' => 'User not found on the system'));

        }

    }


    public function convertDate($date){

        $data = [];

        if (strpos($date, '-')){

            $data = explode("-", $date);

        }else{

            $data = explode("/", $date);

        }


        $converted = "";

        $newDate = "";


        if($date == "N/A" || $date == "NA" || $date == null){

            $newDate = "1/1/2000";

        }else{

            if(count($data) < 3){

                 $newDate = "1/1/2000";

            }else{

                if(is_numeric($data[0]) && is_numeric($data[1]) && is_numeric($data[2])){

                    if(strlen($data[2]) == 2 ){

                        $yearNow = date("y");

                        if ($data[2] > $yearNow) {

                            $converted = '19' . $data[2];

                        } else {

                            $converted = '20' . $data[2];

                        }

                        $newDate2 = $data[0] . "/" . $data[1] . "/" . $converted;

                        $newDate = date("m/d/Y", strtotime($newDate2));

                    }else{

                        $newDate2 = $data[0] . "/" . $data[1]  . "/" . $data[2];

                        $newDate = date("m/d/Y", strtotime($newDate2));

                    }

                }else{

                    $newDate = date("m/d/Y", strtotime($date));

                }


            }

        }


        return $newDate;

    }


    public function updatePreRegistered(Request $request)

    {

         $validate = Validator::make($request->all(), [

            'last_name' => 'required',

            'first_name' => 'required',

            'date_of_birth' => 'required',

            'sex' => 'required',

            'civil_status' => 'required',

            'contact_number' => 'required',

            'barangay' => 'required',

            'profession' => 'required',

            'categories' => 'required',

            'id_categories' => 'required',

            'home_address' => 'required',

        ]);


        if($validate->fails()){

            return response()->json(array('success' => false, 'messages' => 'May be missing required fields, Please check your input.', 'title'=> 'Oops! something went wrong.'));

        }else{


            DB::connection('covid19vaccine')->beginTransaction();

            try{


                /* barangay */

                $barangay = Barangay::findOrFail($request->barangay_obj["id"]);

                $register = PreRegistration::findOrFail($request->id);


                $register->last_name = convertData($request->last_name);

                $register->first_name = convertData($request->first_name);

                $register->middle_name = convertData($request->middle_name);

                $register->suffix = convertData($request->suffix);

                $register->date_of_birth = convertData($request->date_of_birth);

                $register->sex = convertData($request->sex);

                $register->contact_number = convertData($request->contact_number);

                $register->civil_status = convertData($request->civil_status);

                $register->province = 'LAGUNA';

                $register->city = 'CABUYAO';

                $register->barangay = $barangay->barangay;

                $register->barangay_id = convertData($request->barangay_obj['id']);

                $register->home_address = convertData($request->home_address);

                $register->category_id = convertData($request->categories['id']);

                $register->category_id_number = convertData($request->category_id_number);

                $register->philhealth_number = convertData($request->philhealth_number);

                $register->category_for_id = convertData($request->id_categories['id']);

                $changes = $register->getDirty();

                $register->save();

		$is_exists = Guardian::where('id', $request->gurdian_id)->exists();
		if($is_exists){

                $guardian = Guardian::findOrFail($request->gurdian_id);

                $guardian->first_name = convertData($request->gurdian_lname);

                $guardian->last_name = convertData($request->gurdian_fname);

                $guardian->middle_name = (convertData($request->gurdian_mname) == 'N/A')? 'NA' : convertData($request->gurdian_mname);

                $guardian->suffix = convertData($request->gurdian_suffix);

               	$guardian->contact_number = convertData($request->gurdian_contact_number);

                $guardian->relationship = convertData($request->relationship);

               	$guardian->save();
		}



                /* employer */


                $employer = Employer::findOrFail($register->employment_id);

                $employer->employment_status_id = convertData($request->employee_status['id']);

                $employer->profession_id = convertData($request->profession['id']);

                $employer->specific_profession = convertData($request->specific_profession);

                $employer->employer_name = convertData($request->employer_name);

                $employer->employer_contact = convertData($request->employer_contact);

                $employer->employer_barangay_name = convertData($request->employer_address);

                $changes = array_merge($changes, $employer->getDirty());

                $employer->save();


                // $register->save();


                $survey = Survey::findOrFail($request->surveys['id']);

                $survey->question_2 = ($request['withAlergy']) ? 'YES' : 'NO';

                $survey->question_3 = ($request['withAlergy']) ? $request['withAlergyAnswer'] : null;

                $survey->question_4 = ($request['withComorbidities']) ? 'YES' : 'NO';

                $survey->question_5 = ($request['withComorbidities']) ? $request['withComorbiditiesAnswer'] : null;

                $survey->save();


                DB::connection('covid19vaccine')->commit();


                /* logs */

                action_log('Pre-registration mngt', 'UPDATE', array_merge(['id' => $register->id], $changes));


                return response()->json(array('success' => true, 'messages' => 'Record successfully updated'));


            }catch(\PDOException $e){

                DB::connection('covid19vaccine')->rollBack();

                return response()->json(array('success' => false, 'messages' => 'Transaction Failed!','title' => 'Oops! something went wrong.'));

            }

        }

    }


    public function validateUser($data){

        // ->where('middle_name', '=', $data['middlename'])

        // ->where('suffix', '=', $data['suffix'])


        return PreRegistration::where('last_name', '=', $data['lastname'])

            ->where('first_name', '=', $data['firstname'])

            ->where('date_of_birth', '=', $data['dob'])

            ->first();

    }



    public function getUnverifiedPatients(Request $request) {

        if(Auth::user()->account_status == 1){


            try {


                $keyword = $request->search_key;

                $unverified_patients = PreRegistration::with(['categories'])

                ->with(['id_categories'])

                ->with(['surveys'])

                ->with(['employers'])

                ->with(['guardians'])

                // ->select(

                //     'barangay',

                //     'barangay_id',

                //     'category_for_id',

                //     'category_id',

                //     'category_id_number',

                //     'city',

                //     'civil_status',

                //     'contact_number',

                //     'created_at',

                //     'date_of_birth',

                //     'employment_id',

                //     'first_name',

                //     'home_address',

                //     'id',

                //     'image',

                //     'last_name',

                //     'middle_name',

                //     'philhealth_number',

                //     'province',

                //     'registration_code',

                //     'sex',

                //     'status',

                //     'suffix',

                // )

                ->whereRaw("concat(pre_registrations.first_name, ' ', pre_registrations.last_name) like '%{$keyword}%' ")

                ->where('pre_registrations.status', '<>', 2)

                ->paginate($request->items_per_page);


                return PreRegResource::Collection($unverified_patients);


                //return response()->json(['status' => $this->successStatus, 'data' => $unverified_patients, 'message' => 'Patient list retrieved successfully.'], $this->successStatus);


            } catch (\PDOException $e) {


                return response()->json(['status' => $this->errorStatus, 'message' => 'There is an error encountered. Please try again.'], $this->errorStatus);


            }

        } else {

            return response()->json(['status' => $this->errorStatus, 'message' => 'Server error.'], $this->errorStatus);

        }

    }


    public function validatePatient(Request $request) {



        if(Auth::user()->account_status == 1){



            if(Gate::allows('permission', 'viewRegistrationAndValidation')) {


                $validator = Validator::make($request->all(), [

                    'pre_registration_id' => 'required',

                ]);


                if ($validator->fails()) {

                    return response()->json(['error'=>$validator->errors(), 'message' => 'error'], $this->errorStatus);

                }


                // dd($request->pre_registration_id);




                DB::beginTransaction();



                try {


                    $registration_data = VaccinationMonitoring::find($request->pre_registration_id);


                    $qualifiedPatient = new QualifiedPatient;

                    $current_date = Carbon::today();

                    $year = $current_date->year;

                    $day = $current_date->day;

                    $month = $current_date->month;


                    $qualifiedPatient->registration_id = $request->pre_registration_id;

                    $qualifiedPatient->qrcode = 'V' . chr(rand(65,90)) . chr(rand(65,90)) . chr(rand(65,90)) . str_pad($day . substr($year, -2) . $month . $request->pre_registration_id, 16, '0', STR_PAD_LEFT);

                    $qualifiedPatient->qualification_status = "APPROVED";

                    $qualifiedPatient->verified_by = Auth::user()->person->last_name . ", ". Auth::user()->person->first_name . " " . Auth::user()->person->middle_name;

                    $qualifiedPatient->assessment_status = 1;

                    $qualifiedPatient->status = 1;

                    $changes = $qualifiedPatient->getDirty();

                    $qualifiedPatient->save();


                    $preRegistration = PreRegistration::findOrFail($request->pre_registration_id);

                    $preRegistration->status = '0';

                    $preRegistration->save();


                    DB::commit();


                    action_log('Registration Approval', 'CREATE', array_merge(['id' => $qualifiedPatient->id], $changes));


                    return response()->json(['status' => $this->successStatus, 'message' => 'Patient validated successfully.'], $this->successStatus);


                } catch (\PDOException $e) {


                    DB::rollBack();

                    return response()->json(['status' => $this->errorStatus, 'message' => 'There is an error encountered. Please try again.'], $this->errorStatus);


                }


            } else {

                return response()->json(['status' => $this->errorStatus, 'message' => 'You dont have the permission to access this functionality, coordinate with ECabs Administrator regarding with you issue. Please try to re-login your account.'], $this->errorStatus);

            }



        } else {

            return response()->json(['status' => $this->errorStatus, 'message' => 'Server error.'], $this->errorStatus);

        }




    }


    public function getQualifiedPatients(Request $request) {

        if(Auth::user()->account_status == 1){


            try {


                $keyword = $request->search_key;


                if(empty($keyword)){

                    $qualified_patients = QualifiedPatient::with(['vaccination_monitoring'])

                        ->with(['pre_registration' => function($query) use ($request){

                        $query->where(DB::raw("CONCAT(first_name,' ',last_name)"), 'LIKE', "%$request->search_key%");

                        // $query->where("concat(first_name, ' ', last_name) like '%{$request->search_key}%' ");

                    }])

                    // ->select('*', 'qualified_patients.id AS qualified_patient_id')

                    ->leftJoin('surveys as surveys', 'qualified_patients.registration_id', '=', 'surveys.registration_id')

                    ->select(

                        'assessment_status',

                        'deleted_at',

                        'qualified_patients.id',

                        'qrcode',

                        'qualification_status',

                        'question_1',

                        'question_2',

                        'question_3',

                        'question_4',

                        'question_5',

                        'question_6',

                        'question_7',

                        'question_8',

                        'question_9',

                        'question_10',

                        'qualified_patients.registration_id',

                        'qualified_patients.status'

                    )

                    // ->whereHas('vaccination_monitoring', function($query){

                    //     $query->where('status', '=', '1');

                    // })

                    // ->whereHas('vaccination_monitoring', function($query){

                    //     $query->where('status', '=', "1");

                    // })

                    ->where('qualified_patients.qualification_status', '=', 'APPROVED')

                    ->where(function ($query) {

                        $query->where('qualified_patients.assessment_status', '=', "1")

                            ->orWhereNull('qualified_patients.assessment_status');

                    })

                    ->where('qualified_patients.status', '=', '1')

                    ->paginate($request->items_per_page);

                }else{

                    $qualified_patients = QualifiedPatient::with(['vaccination_monitoring'])

                    ->with(['pre_registration'])

                    ->leftJoin('surveys as surveys', 'qualified_patients.registration_id', '=', 'surveys.registration_id')

                    ->select(

                        'assessment_status',

                        'deleted_at',

                        'qualified_patients.id',

                        'qrcode',

                        'qualification_status',

                        'question_1',

                        'question_2',

                        'question_3',

                        'question_4',

                        'question_5',

                        'question_6',

                        'question_7',

                        'question_8',

                        'question_9',

                        'question_10',

                        'qualified_patients.registration_id',

                        'qualified_patients.status',

                    )

                    //->searchData($request->search_key)

                    ->whereHas('pre_registration', function($query) use ($request){

                        // $query->where(DB::raw("CONCAT(first_name,' ',last_name)"), 'LIKE', "%$request->search_key%");

                        $query->whereRaw("concat(first_name, ' ',last_name) like '%{$request->search_key}%' ");

                    })

                    // ->whereHas('vaccination_monitoring', function($query) use ($request){

                    //     $query->where('status', '=', "1");

                    // })

                    // ->whereIn('qualified_patients.registration_id', function($query) use ($request){

                    //     $query->from('pre_registrations')

                    //     ->select('*')

                    //     ->whereRaw("concat(pre_registrations.first_name, ' ',pre_registrations.last_name) like '%{$request->search_key}%' ");

                    // })

                    // ->whereHas('vaccination_monitoring', function($query){

                    //     $query->where('status', '=', '1');

                    // })

                    ->where('qualified_patients.qualification_status', '=', 'APPROVED')

                    ->where(function ($query) {

                        $query->where('qualified_patients.assessment_status', '=', "1")

                            ->orWhereNull('qualified_patients.assessment_status');

                    })

                    ->where('qualified_patients.status', '=', '1')

                    ->paginate($request->items_per_page);

                }


                     return QualifiedPatientResource::Collection($qualified_patients);


                //return response()->json(['status' => $this->successStatus, 'data' => $qualified_patients, 'message' => 'Qualified Patient list retrieved successfully.'], $this->successStatus);



            } catch (\PDOException $e) {

                return response()->json(['status' => $this->errorStatus, 'message' => $e], $this->errorStatus);

            }

        } else {

            return response()->json(['status' => $this->errorStatus, 'message' => 'Server error.'], $this->errorStatus);

        }

    }




    // eto onio

    // |

    // |

    // v

 public function monitorQualifiedPatient(Request $request) {


        if(Auth::user()->account_status == 1){


            if(Gate::allows('permission', 'viewVaccinationMonitoring')) {

                $validator = Validator::make($request->all(), [

                    'dose'=> 'required',

                    'vaccination_date'=> 'required',

                    'vaccine_categories'=> 'required',

                    'batch_number' => 'required',

                    'lot_number'=> 'required',

                    'vaccinators'=> 'required',

                    'consent'=> 'required',

                ]);



                if ($validator->fails()) {

                    return response()->json(['error'=>$validator->errors()], $this->errorStatus);

                }



                DB::connection('covid19vaccine')->beginTransaction();


                $isMonitorCompleted = VaccinationMonitoring::where('qualified_patient_id', '=', $request["qualified_patient_id"])

                                    ->where('dosage', '=', $request["dose"])

                                    ->where('status', '=', '1')

                                    ->first();


                if($isMonitorCompleted){

                    $dosage = "1st";

                    if($request["dose"] == "2"){

                        $dosage = "2nd";

                    }

                    return response()->json(['status' => $this->errorStatus, 'message'=> $dosage . ' dose already completed!'], $this->successStatus);


                }


                try {



                    $vaccinationMonitoring = new VaccinationMonitoring;

                    $vaccinationMonitoring->qualified_patient_id = $request["qualified_patient_id"];

                    $vaccinationMonitoring->dosage = $request["dose"];

                    $vaccinationMonitoring->vaccination_date = $request["vaccination_date"];

                    $vaccinationMonitoring->vaccine_category_id = $request['vaccine_categories']['id'];

                    $vaccinationMonitoring->batch_number = convertData($request['batch_number']);

                    $vaccinationMonitoring->lot_number = convertData($request['lot_number']);

                    $vaccinationMonitoring->vaccinator_id = $request['vaccinators']['id'];

                    $vaccinationMonitoring->consent = convertData($request['consent']);

                    $vaccinationMonitoring->reason_for_refusal = convertData($request['reason_for_refusal']);

                    $vaccinationMonitoring->deferral = convertData($request['deferral']);

                    $vaccinationMonitoring->encoded_by = Auth::user()->person->last_name . ", ". Auth::user()->person->first_name . " " . Auth::user()->person->middle_name;

                    $vaccinationMonitoring->status = 1;

                    $changes = $vaccinationMonitoring->getDirty();


                    $vaccinationMonitoring->save();

                    $monitoringSurvey = new VaccinationMonitoringSurvey;

                    $monitoringSurvey->vaccination_monitoring_id = $vaccinationMonitoring->id;

                    $monitoringSurvey->question_1 = $request['question_1'];

                    $monitoringSurvey->question_2 = $request['question_2'];

                    $monitoringSurvey->question_3 = $request['question_3'];

                    $monitoringSurvey->question_4 = $request['question_4'];

                    $monitoringSurvey->question_5 = $request['question_5'];

                    $monitoringSurvey->question_6 = $request['question_6'];

                    $monitoringSurvey->question_7 = $request['question_7'];

                    $monitoringSurvey->question_8 = $request['question_8'];

                    $monitoringSurvey->question_9 = $request['question8Arr'];

                    $monitoringSurvey->question_10 = $request['question_10'];

                    $monitoringSurvey->question_11 = $request['question_11'];

                    $monitoringSurvey->question_12 = $request['question_12'];

                    $monitoringSurvey->question_13 = $request['question_13'];

                    $monitoringSurvey->question_14 = $request['question_14'];

                    $monitoringSurvey->question_15 = $request['question_15'];

                    $monitoringSurvey->question_16 = $request['question_16'];

                    $monitoringSurvey->question_17 = $request['question17Arr'];

                    $monitoringSurvey->question_18 = $request['question_18'];

                    $monitoringSurvey->question_19 = $request['question_19'];

                    $monitoringSurvey->status = 1;

                    $monitoringSurvey->save();


                    DB::connection('covid19vaccine')->commit();


                    /* logs */

                    action_log('Vaccination Monitoring', 'CREATE', array_merge(['id' => $vaccinationMonitoring->id], $changes));


                    return response()->json(['status' => $this->successStatus, 'message' => 'Qualified Patient monitored successfully.'], $this->successStatus);


                    // return response()->json(array('success' => true, 'messages' => 'Successfully Updated!'));

                } catch (\PDOException $e) {


                    DB::connection('covid19vaccine')->rollBack();

                    return response()->json(['status' => $this->errorStatus, 'message' => 'There is an error encountered. Please try again.'], $this->successStatus);

                    // return response()->json(array('success'=> false, 'error'=>'SQL error!', 'messages'=>'Transaction failed!'));

                }

            } else {

                return response()->json(['status' => $this->errorStatus, 'message' => 'You dont have the permission to access this functionality, coordinate with ECabs Administrator regarding with you issue. Please try to re-login your account.'], $this->errorStatus);

            }



        } else {

            return response()->json(['status' => $this->errorStatus, 'message' => 'Server error.'], $this->errorStatus);

        }



    }


    public function getVaccineCategories() {

        if(Auth::user()->account_status == 1){


            $vaccine_categories = VaccineCategory::where('status', '=', 1)->get();


            return response()->json(['status' => $this->successStatus, 'data' => $vaccine_categories, 'message' => 'Qualified Patient list retrieved successfully.'], $this->successStatus);



            try {

            } catch (\PDOException $e) {

                return response()->json(['status' => $this->errorStatus, 'message' => 'There is an error encountered. Please try again.'], $this->errorStatus);

            }

        } else {

            return response()->json(['status' => $this->errorStatus, 'message' => 'Server error.'], $this->errorStatus);

        }


    }


    public function getVaccinators() {

        if(Auth::user()->account_status == 1){


            $vaccinators = Vaccinator::where('status', 1)->orderBy('last_name')->get();


            return response()->json(['status' => $this->successStatus, 'data' => $vaccinators, 'message' => 'Vaccinators list retrieved successfully.'], $this->successStatus);



            try {

            } catch (\PDOException $e) {

                return response()->json(['status' => $this->errorStatus, 'message' => 'There is an error encountered. Please try again.'], $this->errorStatus);

            }

        } else {

            return response()->json(['status' => $this->errorStatus, 'message' => 'Server error.'], $this->errorStatus);

        }


    }


    public function verifyPassword(Request $request){

        if(Hash::check($request['password'], Auth::user()->password)) {

            return response()->json(array('success'=>true));

        } else {

            return response()->json(array('success'=>false));

        }

    }


    public function updateEditSummary(Request $request)

    {

       
	 $validator = Validator::make($request->all(), [

            'dosage'=> 'required',

            'vaccination_date'=> 'required',

            'vaccine_manufacturer'=> 'required',

            'batch_number' => 'required',

            'lot_number'=> 'required',

            'vaccinator'=> 'required',

            'consent'=> 'required',

        ]);


        DB::connection('covid19vaccine')->beginTransaction();

        try {

            $currentUser = User::join('people', 'people.id', '=', 'users.person_id')

                        ->select(

                        'people.last_name',

                        'people.first_name',

                        'people.middle_name',

                        'people.affiliation'

                        )

            ->where('users.id', '=', Auth::user()->id)->first();


            $encodedBy = $currentUser->last_name;


            if($currentUser->affiliation){

                $encodedBy .= " " . $currentUser->affiliation;

            }


            if($currentUser->first_name){

                $encodedBy .= ", " . $currentUser->first_name . " ";

            }


            if($currentUser->middle_name){

                $encodedBy .= $currentUser->middle_name[0] . ".";

            }


            $vaccinationMonitoring = VaccinationMonitoring::findOrFail($request['id']);;

           // $vaccinationMonitoring->qualified_patient_id = $request["qualified_patient_id"];

            $vaccinationMonitoring->vaccination_date = $request["vaccination_date"];

            $vaccinationMonitoring->vaccine_category_id = $request['vaccine_categories']['id'];

            $vaccinationMonitoring->batch_number = $request['batch_number'];

            $vaccinationMonitoring->lot_number = $request['lot_number'];

            $vaccinationMonitoring->vaccinator_id = $request['vaccinators']['id'];

            $vaccinationMonitoring->consent = convertData($request['consent']);

            $vaccinationMonitoring->reason_for_refusal = convertData($request['reason_for_refusal']);

            $vaccinationMonitoring->deferral = convertData($request['deferral']);

            $vaccinationMonitoring->encoded_by = $encodedBy;

            $vaccinationMonitoring->reason_for_update = convertData($request['reason_for_update']);

            $vaccinationMonitoring->status = 1;

            $changes = $vaccinationMonitoring->getDirty();

            $vaccinationMonitoring->save();


            // $monitoringSurvey = VaccinationMonitoringSurvey::findOrFail($request['survey_id']);

            // $monitoringSurvey->vaccination_monitoring_id = $vaccinationMonitoring->id;

            // $monitoringSurvey->question_1 = $request['edit_question1'];

            // $monitoringSurvey->question_2 = $request['edit_question2'];

            // $monitoringSurvey->question_3 = $request['edit_question3'];

            // $monitoringSurvey->question_4 = $request['edit_question4'];

            // $monitoringSurvey->question_5 = $request['edit_question5'];

            // $monitoringSurvey->question_6 = $request['edit_question6'];

            // $monitoringSurvey->question_7 = $request['edit_question7'];

            // $monitoringSurvey->question_8 = $request['edit_question8'];

            // $monitoringSurvey->question_9 = $request['edit_question9'];

            // $monitoringSurvey->question_10 = $request['edit_question10'];

            // $monitoringSurvey->question_11 = $request['edit_question11'];

            // $monitoringSurvey->question_12 = $request['edit_question12'];

            // $monitoringSurvey->question_13 = $request['edit_question13'];

            // $monitoringSurvey->question_14 = $request['edit_question14'];

            // $monitoringSurvey->question_15 = $request['edit_question15'];

            // $monitoringSurvey->question_16 = $request['edit_question16'];

            // $monitoringSurvey->question_17 = $request['edit_question17'];

            // $monitoringSurvey->question_18 = $request['edit_question18'];

            // $monitoringSurvey->question_19 = $request['edit_question19'];

            // $monitoringSurvey->status = 1;

            // $monitoringSurvey->save();


            DB::connection('covid19vaccine')->commit();


            /* logs */

            action_log('Vaccination Monitoring', 'UPDATE', array_merge(['id' => $vaccinationMonitoring->id], $changes));


            return response()->json(array('success' => true, 'messages' => 'Successfully Updated!'));

        } catch (\PDOException $e) {


            DB::connection('covid19vaccine')->rollBack();

            return response()->json(array('success'=> false, 'error'=>'SQL error!', 'messages'=>'Transaction failed!'));

        }

    }


    public function voidRecord(Request $request)

    {

        DB::connection('covid19vaccine')->beginTransaction();

        try {


            $monitoringSummary = VaccinationMonitoring::where('id', '=', $request->id)->where('status', '=', '1')->first();

            $monitoringSummary->status = 0;

            $changes = $monitoringSummary->getDirty();

            $monitoringSummary->save();


            DB::connection('covid19vaccine')->commit();


            /* logs */

            action_log('Vaccination Monitoring', 'DELETED', array_merge(['id' => $monitoringSummary->id], $changes));


            return response()->json(array('success' => true, 'messages' => 'Successfully Updated!'));

        } catch (\PDOException $e) {

            DB::connection('covid19vaccine')->rollBack();

            return response()->json(array('success'=> false, 'error'=>'SQL error!', 'messages'=>'Transaction failed!'));

        }

    }


    public function vasLineInfo(Request $request)

    {

        $yes_no = array("01_Yes", "02_No");


        $active_region = "REGION IV-A (CALABARZON)";

        $active_province = "043400000Laguna";

        $active_city = "043404000City of Cabuyao";


        $first_dose = "02_No";

        $second_dose = "02_No";


        $category = [

            '01_Health_Care_Worker' => 'A1',

            '02_Senior_Citizen' => 'A2',

            '07_Comorbidities' => 'A3',

            '03_Indigent ' => 'A5',

            '12_Remaining_Workforce' => 'A4',

            '11_OFW' => 'A4',

            '10_Other_High_Risk' => 'A4',

            '09_Other_Govt_Wokers' => 'A4',

            '08_Teachers_Social_Workers' => 'A4',

            '06_Other' => 'A4',

            '05_Essential_Worker' => 'A4',

            '04_Uniformed_Personnel' => 'A4',

        ];


        $vaccine = [

            "SINOVAC" => "Sinovac",

            "ASTRAZENECA" => "AZ",

            "PFIZER" => "Pfizer",

            "MODERNA" => "Moderna",

            "SPUTNIK V/GAMALEYA" => "Gamaleya",

            "NOVAVAX" => "Novavax",

            "JOHNSON AND JOHNSON" => "J&J",

        ];



        $center = [

            "CABUYAO CHO I – BAKUNA CENTER" => "CBC07609",

            "CABUYAO CHO II – BAKUNA CENTER" => "CBC07625",

            "CABUYAO CITY HOSPITAL" => "CBC06192",

            "HOLY ROSARY OF CABUYAO HOSPITAL INC." => "CBC06191",

            "FIRST CABUYAO HOSPITAL AND MEDICAL CENTER, INC." => "CBC06190",

            "GLOBAL MEDICAL CENTER OF LAGUNA" => "CBC06260",

        ];


        // $vaccination_monitoring = ExportHasPatient::join('vaccination_monitorings as vaccination_monitorings', 'vaccination_monitorings.id', '=',  'export_has_patients.patient_id')

        $vaccination_monitorings = VaccinationMonitoring::

            join('qualified_patients as qualified_patients', 'qualified_patients.id', '=', 'vaccination_monitorings.qualified_patient_id')

            ->join('pre_registrations as pre_registrations', 'pre_registrations.id', '=', 'qualified_patients.registration_id')

            ->join('categories as categories', 'categories.id', '=', 'pre_registrations.category_id')

            ->join('id_categories as id_categories', 'id_categories.id', '=', 'pre_registrations.category_for_id')

            ->join('vaccination_monitoring_surveys as vaccination_monitoring_surveys', 'vaccination_monitoring_surveys.vaccination_monitoring_id', '=', 'vaccination_monitorings.id')

            ->join('vaccinators as vaccinators', 'vaccinators.id', '=', 'vaccination_monitorings.vaccinator_id')

            ->join('health_facilities as health_facilities', 'health_facilities.id', '=', 'vaccinators.health_facilities_id')

            ->join('vaccine_categories as vaccine_categories', 'vaccine_categories.id', '=', 'vaccination_monitorings.vaccine_category_id')

            ->join('barangays as barangays', 'barangays.id', '=', 'pre_registrations.barangay_id')

            ->select(

                'categories.category_format',

                'id_categories.id_category_code',

                'id_categories.id as id_category',

                'pre_registrations.category_id_number',

                'pre_registrations.philhealth_number',

                'pre_registrations.barangay_id',

                'pre_registrations.last_name',

                'pre_registrations.first_name',

                'pre_registrations.middle_name',

                'pre_registrations.suffix',

                'pre_registrations.contact_number',

                'pre_registrations.home_address',

                'pre_registrations.status',

                'pre_registrations.province',

                'pre_registrations.city',

                'barangays.real_name as barangay',

                'pre_registrations.sex',

                'pre_registrations.date_of_birth',

                'qualified_patients.qrcode',

                'qualified_patients.id as qualified_patient__id',



                'vaccination_monitorings.id as vaccination_monitorings_id',

                'vaccination_monitorings.consent',

                'vaccination_monitorings.reason_for_refusal',

                'vaccination_monitorings.vaccination_date',

                'vaccine_categories.vaccine_name as vaccine_manufacturer',

                'vaccination_monitorings.batch_number',

                'vaccination_monitorings.lot_number',

                'vaccination_monitorings.deferral',

                'vaccination_monitorings.dosage',

                'vaccinators.last_name as vaccinator_lastname',

                'vaccinators.first_name as vaccinator_firstname',

                'vaccinators.suffix',

                'vaccinators.profession',

                'health_facilities.facility_name',


                'vaccination_monitoring_surveys.question_1 as age_validation',

                'vaccination_monitoring_surveys.question_2 as allergic_for_peg',

                'vaccination_monitoring_surveys.question_3 as allergic_after_dose',

                'vaccination_monitoring_surveys.question_4 as allergic_to_food',

                'vaccination_monitoring_surveys.question_5 as asthma_validation',

                'vaccination_monitoring_surveys.question_6 as bleeding_disorders',

                'vaccination_monitoring_surveys.question_7 as syringe_validation',

                'vaccination_monitoring_surveys.question_8 as symptoms_manifest',

                'vaccination_monitoring_surveys.question_9 as symptoms_specific',

                'vaccination_monitoring_surveys.question_10 as infection_history',

                'vaccination_monitoring_surveys.question_11 as previously_treated',

                'vaccination_monitoring_surveys.question_12 as received_vaccine',

                'vaccination_monitoring_surveys.question_13 as received_convalescent',

                'vaccination_monitoring_surveys.question_14 as pregnant',

                'vaccination_monitoring_surveys.question_15 as pregnancy_trimester',

                'vaccination_monitoring_surveys.question_16 as diagnosed_six_months',

                'vaccination_monitoring_surveys.question_17 as specific_diagnosis',

                'vaccination_monitoring_surveys.question_18 as medically_cleared'


            )

            ->where('qualified_patients.id', '=', $request->id)->get();

        $data = array();

        foreach($vaccination_monitorings as $vaccination_monitoring){


            if($vaccination_monitoring->dosage == 1) {

                $first_dose = "01_Yes";

                $second_dose = "02_No";

            } else {

                $first_dose = "01_Yes";

                $second_dose = "01_Yes";

            }


            $nestedData["0"] = $category[$vaccination_monitoring->category_format];

            $nestedData["1"] = $vaccination_monitoring->qrcode; //government ID

            $nestedData["2"] = ($vaccination_monitoring->id_category_code) == "04 - PWD ID"? "Y" : "N";

            $nestedData["3"] = "NO";

            $nestedData["4"] = $vaccination_monitoring->last_name;

            $nestedData["5"] = $vaccination_monitoring->first_name;

            $nestedData["6"] = $vaccination_monitoring->middle_name;

            $nestedData["7"] = ($vaccination_monitoring->suffix == null)? "NA" : $vaccination_monitoring->suffix;

            $nestedData["8"] = $vaccination_monitoring->contact_number;

            $nestedData["9"] = $active_region;

            $nestedData["10"] = $active_province;

            $nestedData["11"] = $active_city;

            $nestedData["12"] = $vaccination_monitoring->barangay;

            $nestedData["13"] = ($vaccination_monitoring->sex) == '1_FEMALE'? "F" : "M";

            $nestedData["14"] = $vaccination_monitoring->date_of_birth;

            $nestedData["15"] = "N"; //deferral

            $nestedData["16"] = "NONE"; // reason for deferral

            $nestedData["17"] = $vaccination_monitoring->vaccination_date;

            $nestedData["18"] = $vaccine[$vaccination_monitoring->vaccine_manufacturer];

            $nestedData["19"] = $vaccination_monitoring->batch_number;

            $nestedData["20"] = $vaccination_monitoring->lot_number;

            $nestedData["21"] = $center[$vaccination_monitoring->facility_name];

            $nestedData["22"] = $vaccination_monitoring->vaccinator_lastname . ", " . $vaccination_monitoring->vaccinator_firstname;

            $nestedData["23"] = ($first_dose == "01_Yes") ? 'Y' : 'N';

            $nestedData["24"] = ($second_dose == "01_Yes") ? 'Y' : 'N';

            $nestedData["25"] = "N";

            $nestedData["26"] = "NONE";


            $data[] = $nestedData;

        }

        return $data;

    }



}

