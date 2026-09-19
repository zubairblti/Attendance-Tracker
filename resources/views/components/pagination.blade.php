@if ($paginator->hasPages())
<nav class="crm-pagination" role="navigation" aria-label="Pagination Navigation">
 @if(method_exists($paginator,'total'))<p class="pagination-summary">Showing <strong>{{ $paginator->firstItem()??0 }}</strong> to <strong>{{ $paginator->lastItem()??0 }}</strong> of <strong>{{ $paginator->total() }}</strong> results</p>@else<p class="pagination-summary">Page <strong>{{ $paginator->currentPage() }}</strong></p>@endif
 <div class="pagination-links">
  @if($paginator->onFirstPage())<span class="pagination-link disabled" aria-disabled="true">Previous</span>@else<a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>@endif
  @if(method_exists($paginator,'lastPage'))
   @foreach($elements as $element)
    @if(is_string($element))<span class="pagination-ellipsis">{{ $element }}</span>@endif
    @if(is_array($element))@foreach($element as $page=>$url)@if($page==$paginator->currentPage())<span class="pagination-link active" aria-current="page">{{ $page }}</span>@else<a class="pagination-link" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>@endif @endforeach @endif
   @endforeach
  @endif
  @if($paginator->hasMorePages())<a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>@else<span class="pagination-link disabled" aria-disabled="true">Next</span>@endif
 </div>
</nav>
@endif
