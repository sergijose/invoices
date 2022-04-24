<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceStoreRequest;
use App\Mail\InvoiceMail;
use App\Models\Buyer;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $invoices = Invoice::with('buyer')->paginate(3);
        return view('invoices.index', compact('invoices'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $invoice = new Invoice();
        $buyers = Buyer::all();
        return view('invoices.create', compact('invoice', 'buyers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(InvoiceStoreRequest $request)
    {
        $invoice = Invoice::create($request->validated());
        return redirect()->route('invoices.add_products', ["invoice" => $invoice->id])->with(['status' => 'Success', 'color' => 'green', 'message' => 'Item added successfully']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
        $buyers = Buyer::all();
        return view('invoices.create', compact('invoice','buyers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Invoice $invoice)
    {
        $invoice->fill($request->validated());
        $invoice->save();
        return redirect()->route('invoices.index')->with(['status' => 'Success', 'color' => 'blue', 'message' => 'Product updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        try {
            $invoice->delete();
            $result = ['status' => 'success', 'color' => 'green', 'message' => 'Deleted successfully'];
        } catch (\Exception $e) {
            $result = ['status' => 'error', 'color' => 'red', 'message' => 'Invoice cannot be delete'];
        }

        return redirect()->route('invoices.index')->with($result);
    }



    public function completeSend(Request $request, Invoice $invoice)
    {
        $details = InvoiceDetail::with('product')
            ->where('invoice_id', $invoice->id)
            ->get();

        try {
            Mail::to($invoice->buyer->email)
                ->queue(new InvoiceMail($invoice, $details));
            $result = ['status' => 'success', 'color' => 'green', 'message' => 'Mail sent successfully'];
        } catch (\Exception $e) {
            $result = ['status' => 'success', 'color' => 'green', 'message' => $e->getMessage()];
        }

        $invoice->status = 'complete';
        $invoice->save();

        return redirect()->route('invoices.index')->with($result);
    }
}
