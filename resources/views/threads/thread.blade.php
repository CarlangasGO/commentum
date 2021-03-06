@extends('layouts.default')
@section('title', $thread->title.' | #'.$thread->tag()->display_title)

@section('page')
<div class="hero">
	@if (Auth::check())
	@include('layouts.user-header')
	@include('layouts.subscriptions-list')
	<hr>
	@endif
	<div class="row">
		<div class="medium-12 columns">
			<table>
				<tr>
					<td>
						{{ floor($thread->calculateMomentum()) }}
					</td>
					<td>
						<h1 class="thread-title">
							{!! (!empty($thread->link) ? '<i class="ion-link"></i>' : '') !!}
							<a href="{{ $thread->titlePermalink() }}">{{ $thread->title }}</a>
						</h1>
						<p>
							<a href="{{ $thread->tag()->permalink() }}"><span data-livestamp="{{ strtotime($thread->created_at) }}"></span> in #{{ $thread->tag()->display_title }}</a> by <a href="{{ $thread->author()->permalink() }}">{{ $thread->author()->username }}</a>
							<br>
							<span class="info-and-options">
								@if ($thread->nsfw)
								<span class="thread-tag nsfw">nsfw</span>
								@endif
								@if ($thread->serious)
								<span class="thread-tag serious">serious</span>
								@endif
								@if ($thread->nsfw || $thread->serious)
								<br>
								@endif
								<span>{{ $thread->views }} view{{ ($thread->views != 1 ? 's' : '') }}</span>
								@if (Auth::check())
								<span>&middot;</span>
								<span><a id="saveThread" data-hashid="{{ Hashids::encode($thread->id) }}">{{ (Auth::user()->savedThread($thread->id) == true ? "un" : "") }}save</a></span>
								@if (Auth::id() == $thread->author()->id || Auth::user()->can('edit-thread'))
								<span>&middot;</span>
								<a href="{{ $thread->permalink() }}/edit">edit</a>
								@endif
								@if (Auth::id() == $thread->author()->id || Auth::user()->can('remove-thread') || $thread->tag()->isMod())
								<span>&middot;</span>
								<a id="deleteThread">delete</a>
								@endif
								@endif
							</span>
						</p>
						@if (!is_null($thread->markdown) && !empty($thread->markdown))
						<br>
						<div class="markdown thread-description">
							{{ $thread->markdown }}
						</div>
						@endif
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
<div class="padding">
	<div class="row">
		<div class="medium-12 columns">
			{!! Form::open(['url' => '/comment', 'class' => 'row comment-box parent-commenter', 'data-hierarchy' => 'parent', 'onsubmit' => 'submitComment(event, this)']) !!}
				{!! Form::hidden('thread_id', Hashids::encode($thread->id)) !!}
				{!! Form::hidden('parent_id', Hashids::encode(0)) !!}
				<div class="medium-5 large-3 columns">
					{!! Form::label('markdown', 'Post a comment') !!}
					<p class="no-margin">
						You can use <a href="{{ url('/') }}">Markdown</a>.
					</p>
					{!! Form::textarea('markdown', '', ['rows' => 4, 'class' => 'comment-textarea']) !!}
					<div class="preview hide">
						<h6 class="super-header">Live Preview</h6>
						<div class="markdown"></div>
					</div>
					<p class="text-alert"></p>
					{!! Form::submit('Submit', ['class' => 'btn']) !!}
				</div>
			{!! Form::close() !!}
		</div>
		<div class="medium-12 columns">
			<hr>
			<p class="super-header light"><span id="threadCommentCount">{{ $thread->commentCount() }}</span> comment<span id="threadCommentPlural">{{ ($thread->commentCount() > 1 || $thread->commentCount() === 0 ? 's' : '') }}</span></p>
			<div class="comments-list children" id="commentsList">
				@if(isset($singleComment))
					@if ($context == true)
					@include('layouts.comment', ['c' => $singleComment->parent(), 'indent' => 2, 'threadId' => $thread->id])
					@else
					@include('layouts.comment', ['c' => $singleComment, 'indent' => 2, 'threadId' => $thread->id])
					@endif
				@else
				{!! $thread->printComments() !!}
				@endif
			</div>
		</div>
	</div>
</div>
<input type="hidden" value="{{ csrf_token() }}" id="csrfToken">
@stop

@section('scripts')
{!! HTML::script('/bower_components/marked/marked.min.js') !!}
{!! HTML::script('/bower_components/livestamp/moment.min.js') !!}
{!! HTML::script('/bower_components/livestamp/livestamp.min.js') !!}
@include('scripts.threads-user-header')
@include('scripts.markdown-parser')
@include('scripts.expando')
@include('scripts.commenter', ['threadUserId' => $thread->user_id])
@if (Auth::check())
	@include('scripts.thread-actions')
	@if (Auth::id() == $thread->author()->id || Auth::user()->can('remove-thread') || $thread->tag()->isMod())
		@include('scripts.thread-deleter')
	@endif
@endif
@stop
