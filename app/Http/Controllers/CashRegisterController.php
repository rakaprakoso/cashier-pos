<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\CashRegister;
use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CashRegisterController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $cashRegisterUtil;
    protected $moduleUtil;
    protected $contactUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $productUtil;

    /**
     * Constructor
     *
     * @param CashRegisterUtil $cashRegisterUtil
     * @return void
     */
    public function __construct(CashRegisterUtil $cashRegisterUtil, ModuleUtil $moduleUtil,ContactUtil $contactUtil, BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;

        $this->dummyPaymentLine = ['method' => '', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
        'is_return' => 0, 'transaction_no' => ''];

        $this->shipping_status_colors = [
            'ordered' => 'bg-yellow',
            'packed' => 'bg-info',
            'shipped' => 'bg-navy',
            'delivered' => 'bg-green',
            'cancelled' => 'bg-red',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('cash_register.index');
    }
    public function index_group()
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');
        // $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
        // $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
        // $is_types_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, false);

        $data = CashRegister::get();

        if (request()->ajax()) {
            $data = CashRegister::get();
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $data = CashRegister::
                whereDate('created_at', '>=', $start)
                            ->whereDate('created_at', '<=', $end)
                // ->whereDate('closed_at', '>=', $start)
                //             ->whereDate('closed_at', '<=', $end)
                            ->get();
            }
            // return $data;
            $datatable = DataTables::of($data)
                ->addColumn(
                    'action',
                    function ($row) use ($is_admin) {
                        $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                        data-toggle="dropdown" aria-expanded="false">' .
                                        __("messages.actions") .
                                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                // ->removeColumn('id')
                // ->editColumn(
                //     'final_total',
                //     '<span class="final-total" data-orig-value="{{$final_total}}">@format_currency($final_total)</span>'
                // )
                // ->editColumn(
                //     'tax_amount',
                //     '<span class="total-tax" data-orig-value="{{$tax_amount}}">@format_currency($tax_amount)</span>'
                // )
                // ->editColumn(
                //     'total_paid',
                //     '<span class="total-paid" data-orig-value="{{$total_paid}}">@format_currency($total_paid)</span>'
                // )
                // ->editColumn(
                //     'total_before_tax',
                //     '<span class="total_before_tax" data-orig-value="{{$total_before_tax}}">@format_currency($total_before_tax)</span>'
                // )
                // ->editColumn(
                //     'discount_amount',
                //     function ($row) {
                //         $discount = !empty($row->discount_amount) ? $row->discount_amount : 0;

                //         if (!empty($discount) && $row->discount_type == 'percentage') {
                //             $discount = $row->total_before_tax * ($discount / 100);
                //         }

                //         return '<span class="total-discount" data-orig-value="' . $discount . '">' . $this->transactionUtil->num_f($discount, true) . '</span>';
                //     }
                // )
                // ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'closing_amount',
                    '<span class="total-paid" data-orig-value="{{$closing_amount}}">@format_currency($closing_amount)</span>'

                    // function ($row) {
                    //     $payment_status = Transaction::getPaymentStatus($row);
                    //     return (string) view('sell.partials.payment_status', ['payment_status' => $payment_status, 'id' => $row->id]);
                    // }
                )
                // ->editColumn(
                //     'types_of_service_name',
                //     '<span class="service-type-label" data-orig-value="{{$types_of_service_name}}" data-status-name="{{$types_of_service_name}}">{{$types_of_service_name}}</span>'
                // )
                // ->addColumn('total_remaining', function ($row) {
                //     $total_remaining =  $row->final_total - $row->total_paid;
                //     $total_remaining_html = '<span class="payment_due" data-orig-value="' . $total_remaining . '">' . $this->transactionUtil->num_f($total_remaining, true) . '</span>';

                    
                //     return $total_remaining_html;
                // })
                // ->addColumn('return_due', function ($row) {
                //     $return_due_html = '';
                //     if (!empty($row->return_exists)) {
                //         $return_due = $row->amount_return - $row->return_paid;
                //         $return_due_html .= '<a href="' . action("TransactionPaymentController@show", [$row->return_transaction_id]) . '" class="view_purchase_return_payment_modal"><span class="sell_return_due" data-orig-value="' . $return_due . '">' . $this->transactionUtil->num_f($return_due, true) . '</span></a>';
                //     }

                //     return $return_due_html;
                // })
                // ->editColumn('invoice_no', function ($row) {
                //     $invoice_no = $row->invoice_no;
                //     if (!empty($row->woocommerce_order_id)) {
                //         $invoice_no .= ' <i class="fab fa-wordpress text-primary no-print" title="' . __('lang_v1.synced_from_woocommerce') . '"></i>';
                //     }
                //     if (!empty($row->return_exists)) {
                //         $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned_from_sell') .'"><i class="fas fa-undo"></i></small>';
                //     }
                //     if (!empty($row->is_recurring)) {
                //         $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.subscribed_invoice') .'"><i class="fas fa-recycle"></i></small>';
                //     }

                //     if (!empty($row->recur_parent_id)) {
                //         $invoice_no .= ' &nbsp;<small class="label bg-info label-round no-print" title="' . __('lang_v1.subscription_invoice') .'"><i class="fas fa-recycle"></i></small>';
                //     }

                //     return $invoice_no;
                // })
                // ->editColumn('shipping_status', function ($row) use ($shipping_statuses) {
                //     $status_color = !empty($this->shipping_status_colors[$row->shipping_status]) ? $this->shipping_status_colors[$row->shipping_status] : 'bg-gray';
                //     $status = !empty($row->shipping_status) ? '<a href="#" class="btn-modal" data-href="' . action('SellController@editShipping', [$row->id]) . '" data-container=".view_modal"><span class="label ' . $status_color .'">' . $shipping_statuses[$row->shipping_status] . '</span></a>' : '';
                     
                //     return $status;
                // })
                // ->addColumn('conatct_name', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br> @endif {{$name}}')
                // ->editColumn('total_items', '{{@format_quantity($total_items)}}')
                // ->filterColumn('conatct_name', function ($query, $keyword) {
                //     $query->where( function($q) use($keyword) {
                //         $q->where('contacts.name', 'like', "%{$keyword}%")
                //         ->orWhere('contacts.supplier_business_name', 'like', "%{$keyword}%");
                //     });
                // })
                ;

            $rawColumns = ['action', 'closing_amount'];
                
            return $datatable
                    ->rawColumns($rawColumns)
                    ->make(true);
        }

        return view('cash_register.index_group')
        ->with(compact('data','business_locations','is_woocommerce'));
        return $data;
        return view('cash_register.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //like:repair
        $sub_type = request()->get('sub_type');

        //Check if there is a open register, if yes then redirect to POS screen.
        if ($this->cashRegisterUtil->countOpenedRegister() != 0) {
            return redirect()->action('SellPosController@create', ['sub_type' => $sub_type]);
        }
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('cash_register.create')->with(compact('business_locations', 'sub_type'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //like:repair
        $sub_type = request()->get('sub_type');
            
        try {
            $initial_amount = 0;
            if (!empty($request->input('amount'))) {
                $initial_amount = $this->cashRegisterUtil->num_uf($request->input('amount'));
            }
            $user_id = $request->session()->get('user.id');
            $business_id = $request->session()->get('user.business_id');

            $register = CashRegister::create([
                        'business_id' => $business_id,
                        'user_id' => $user_id,
                        'status' => 'open',
                        'location_id' => $request->input('location_id'),
                        'created_at' => \Carbon::now()->format('Y-m-d H:i:00')
                    ]);
            if (!empty($initial_amount)) {
                $register->cash_register_transactions()->create([
                            'amount' => $initial_amount,
                            'pay_method' => 'cash',
                            'type' => 'credit',
                            'transaction_type' => 'initial'
                        ]);
            }
            
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
        }

        return redirect()->action('SellPosController@create', ['sub_type' => $sub_type]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CashRegister  $cashRegister
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('view_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $register_details =  $this->cashRegisterUtil->getRegisterDetails($id);
        $user_id = $register_details->user_id;
        $open_time = $register_details['open_time'];
        $close_time = !empty($register_details['closed_at']) ? $register_details['closed_at'] : \Carbon::now()->toDateTimeString();
        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time);

        $payment_types = $this->cashRegisterUtil->payment_types(null, false, $business_id);

        return view('cash_register.register_details')
                    ->with(compact('register_details', 'details', 'payment_types', 'close_time'));
    }

    /**
     * Shows register details modal.
     *
     * @param  void
     * @return \Illuminate\Http\Response
     */
    public function getRegisterDetails()
    {
        if (!auth()->user()->can('view_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $register_details =  $this->cashRegisterUtil->getRegisterDetails();

        $user_id = auth()->user()->id;
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();

        $is_types_of_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled);

        $payment_types = $this->cashRegisterUtil->payment_types($register_details->location_id, true, $business_id);
        
        return view('cash_register.register_details')
                ->with(compact('register_details', 'details', 'payment_types', 'close_time'));
    }

    /**
     * Shows close register form.
     *
     * @param  void
     * @return \Illuminate\Http\Response
     */
    public function getCloseRegister($id = null)
    {
        if (!auth()->user()->can('close_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $register_details =  $this->cashRegisterUtil->getRegisterDetails($id);

        $user_id = $register_details->user_id;
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();

        $is_types_of_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled);
        
        $payment_types = $this->cashRegisterUtil->payment_types($register_details->location_id, true, $business_id);
        return view('cash_register.close_register_modal')
                    ->with(compact('register_details', 'details', 'payment_types'));
    }

    /**
     * Closes currently opened register.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postCloseRegister(Request $request)
    {
        if (!auth()->user()->can('close_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            //Disable in demo
            if (config('app.env') == 'demo') {
                $output = ['success' => 0,
                                'msg' => 'Feature disabled in demo!!'
                            ];
                return redirect()->action('HomeController@index')->with('status', $output);
            }
            
            $input = $request->only(['  ', 'total_card_slips', 'total_cheques',
                                    'closing_note']);
            $input['closing_amount'] = $this->cashRegisterUtil->num_uf($input['closing_amount']);
            $user_id = $request->input('user_id');
            $input['closed_at'] = \Carbon::now()->format('Y-m-d H:i:s');
            $input['status'] = 'close';

            CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->update($input);
            $output = ['success' => 1,
                            'msg' => __('cash_register.close_success')
                        ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = ['success' => 0,
                            'msg' => __("messages.something_went_wrong")
                        ];
        }

        return redirect()->back()->with('status', $output);
    }
}
