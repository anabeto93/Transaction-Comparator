@extends('layouts.app')

@section('content')
<div class="container">
    <!-- Files Upload Section -->
    <div class="row row-section">
        <form action="{{ route('transactions.compare') }}" method="POST" enctype="multipart/form-data"  class="col-md-10 offset-md-1">
            @csrf
            <div class="row">
                <h4>Select Files To Compare</h4>
                <div class="col-md-6">
                    <div class="control-fileupload" id="file1">
                        <label for="csv_file1">Choose File 1: </label>
                        <input type="file" name="csv_file1" id="csv_file1" accept=".csv">
                    </div>
                    @if($errors->has('csv_file1'))
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $errors->first('csv_file1') }}</strong>
                    </span>
                    @endif
                </div>
                <div class="col-md-6">
                    <div class="control-fileupload" id="file2">
                        <label for="csv_file2">Choose File 2: </label>
                        <input type="file" name="csv_file2" id="csv_file2" accept=".csv">
                    </div>
                    @if($errors->has('csv_file2'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('csv_file2') }}</strong>
                        </span>
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 offset-md-4" id="submitBtn">
                    <button class="btn btn-primary btn-sm" type="submit">Compare</button>
                </div>
            </div>
        </form>
    </div>
    @if(isset($results) && is_array($results) && isset($names) && is_array($names))
    <!-- Comparison Results Section -->
    <div class="row row-section">
        <div class="col-md-10 offset-md-1">
            <h4>Comparison Results</h4>
            <div class="row results-section">
                <div class="col-md-5 file-details">
                    <span class="file_name">{{ $names['file1'] }}</span>
                    <div class="row">
                        <div class="col-md-6">
                            <span>Total Records:</span>
                            <span>Matching Records:</span>
                            <span>Unmatched Records:</span>
                        </div>
                        <div class="col-md-6 data-section">
                            <span>{{ $results['file1']['total'] }}</span>
                            <span>{{ $results['file1']['matching'] }}</span>
                            <span>{{ $results['file1']['unmatched'] }}</span>
                        </div>
                    </div>

                </div>
                <div class="col-md-5 file-details">
                    <span class="file_name">{{ $names['file2'] }}</span>
                    <div class="row">
                        <div class="col-md-6">
                            <span>Total Records:</span>
                            <span>Matching Records:</span>
                            <span>Unmatched Records:</span>
                        </div>
                        <div class="col-md-6 data-section">
                            <span>{{ $results['file2']['total'] }}</span>
                            <span>{{ $results['file2']['matching'] }}</span>
                            <span>{{ $results['file2']['unmatched'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 offset-md-4" id="reportSection">
                    <button class="btn btn-primary btn-sm" id="reportBtn">Unmatched Report</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
<div class="col-md-10 offset-md-1">
@if(isset($reports) && is_array($reports) && isset($names) && is_array($names))
    <!-- Unmatched Reports Section -->
        <div class="row row-section" id="unmatchedReportSection">
            <h4>Unmatched Report</h4>
            <div class="file-report-table">
                <span class="file_name">{{ $names['file1'] }}</span>
                <table class="table table-striped" id="table1">
                    <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Reference</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Similar Reference</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($reports['file1'] as $report)
                        <tr>
                            {{--<th scope="row"></th>--}}
                            <td>{{ $report['date'] }}</td>
                            <td>{{ $report['reference'] }}</td>
                            <td>{{ $report['amount'] }}</td>
                            <td>{{ $report['advice'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="file-report-table">
                <span class="file_name">{{ $names['file2'] }}</span>
                <table class="table table-striped" id="table2">
                    <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Reference</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Advice</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($reports['file2'] as $report)
                        <tr>
                            {{--<th scope="row"></th>--}}
                            <td>{{ $report['date'] }}</td>
                            <td>{{ $report['reference'] }}</td>
                            <td>{{ $report['amount'] }}</td>
                            <td>{{ $report['advice'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.10.18/js/jquery.dataTables.min.js" defer></script>
<script src="https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js" defer></script>
<script>
    window.onload = function() {
        $.each(['#csv_file1','#csv_file2'], function(i, id) {
            $(id).change(function(){
                let t = $(this).val();
                let labelText = 'File : ' + t.substr(12, t.length);
                $(this).prev('label').text(labelText)
            })
        })

        //Show the Unmatched Report Section when the button is clicked
        $('#reportBtn').on('click', function() {
            $('#unmatchedReportSection').css({ 'display': 'flex'})
        })
        $.each(['#table1','#table2'], function(i, id) {
            $(id).DataTable()
        })
    }
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.18/css/dataTables.bootstrap4.min.css">
<style>
    .row-section {
        border: 1px solid #95999c;
        border-radius: .65rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .row h4 {
        display: block;
        width: 100%;
        text-align: center;
        margin-top: 1.5rem;
    }
    #submitBtn, #reportSection {
        margin-top: 1rem;
        text-align: center;
    }
    input[type=file] {
        display: block !important;
        right: 1px;
        top: 1px;
        height: 34px;
        opacity: 0;
        width: 100%;
        background: none;
        position: absolute;
        overflow: hidden;
        z-index: 2;
    }
    .control-fileupload {
        display: block;
        border: 1px solid #d6d7d6;
        background: #FFF;
        border-radius: 4px;
        width: 100%;
        height: 36px;
        line-height: 36px;
        padding:0 10px 2px 10px;
        overflow: hidden;
        position: relative;
        /* File upload button */
    }
    .control-fileupload:before,
    .control-fileupload input,
    .control-fileupload label {
        cursor: pointer !important;
    }
    .control-fileupload:before {
        /* inherit from boostrap btn styles */
        padding: 4px 12px;
        margin-bottom: 0;
        font-size: 14px;
        color: #333333;
        text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75);
        vertical-align: middle;
        cursor: pointer;
        background-color: #f5f5f5;
        background-image: linear-gradient(to bottom, #ffffff, #e6e6e6);
        background-repeat: repeat-x;
        border: 1px solid #cccccc;
        border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
        border-bottom-color: #b3b3b3;
        border-radius: 4px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: color 0.2s ease;
        /* add more custom styles*/
        content: 'Browse';
        display: block;
        position: absolute;
        z-index: 1;
        top: 2px;
        right: 2px;
        line-height: 20px;
        text-align: center;
    }
    .control-fileupload:hover:before,
    .control-fileupload:focus:before {
        background-color: #e6e6e6;
        color: #333333;
        text-decoration: none;
        background-position: 0 -15px;
        transition: background-position 0.2s ease-out;
    }
    .control-fileupload label {
        line-height: 24px;
        color: #999999;
        font-size: 14px;
        font-weight: normal;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        position: relative;
        z-index: 1;
        margin-right: 90px;
        margin-bottom:0;
        cursor: text;
    }
    .results-section {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
    }
    .file-details {
        border: 2px solid gray;
        border-radius: 10px;
        padding: 10px 25px;
    }
    .file_name {
        font-weight: bolder;
        font-size: 17px;
        letter-spacing: 2px;
    }
    .file-details span {
        display: block;
    }
    .file-details .data-section {
        text-align: left;
        font-weight: 700;
    }
    .file-report-table {
        width: 50%;
        padding: 0;
        display: inline-block;
    }
    .file-report-table span {
        display: block;
    }
    .file-report-table .file_name {
        text-align: center;
    }
    #unmatchedReportSection {
        display: none;
        font-size: 12px;
    }
    .invalid-feedback {
        display: inline-block;
    }
</style>
@endpush
