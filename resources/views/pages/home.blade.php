@extends('main')
@section('content')

<div class="card-body">
    @if(isset($failedLogin))
        <div class="alert alert-danger" role="alert">
            <div style="text-align: center"><b>Fehler beim Anmelden!</b></div>
        </div>
    @endif
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <a href="https://github.com/ilyes-plenty/GitHub-Client">
                                <div style="text-align: center"><img width="70%" height="70%" src="https://marketplace-cdn.atlassian.com/files/images/cec44feb-0b1b-4fe3-936d-67a51a1fe28e.png" alt="GitHub Page"></div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@stop