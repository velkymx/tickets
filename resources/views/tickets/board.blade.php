@extends('layouts.app')
@section('title')
Ticket Board
@endsection
<!-- Main Content -->
@section('content')

  <h1>Ticket Board</h1>
  <div id="update" style="display:none">

  </div>
  <div class="row" style="white-space: nowrap;overflow: auto;">
    <table>
      <tr>
    @foreach ($lookups['statuses'] as $key => $value)
      <td valign="top" style="padding:10px">
        <div style="width:240px" class="panel panel-default" id="status[{{$key}}]">
          <div class="panel-heading">{{$value}}</div>
          <ol class="simple_with_animation vertical" id="{{$key}}">
          @foreach ($tickets->where('status_id',$key) as $ticket)
            <li id="{{$ticket->id}}">
              <a href="/tickets/{{$ticket->id}}">{{$ticket->subject}}</a>
            </li>
          @endforeach
        </ol>
        </div>
      </td>
    @endforeach
    </tr>
    </table>
  </div>
@endsection
@section('javascript')
  <script src="/js/jquery-sortable.js" charset="utf-8"></script>
  <script type="text/javascript">
  var adjustment;

  $("ol.simple_with_animation").sortable({
    group: 'simple_with_animation',
    pullPlaceholder: false,
    // animation on drop
    onDrop: function  ($item, container, _super) {
      var $clonedItem = $('<li/>').css({height: 0});
      $item.before($clonedItem);
      $clonedItem.animate({'height': $item.height()});

      $item.animate($clonedItem.position(), function  () {
        $clonedItem.detach();
        _super($item, container);
      });

      $id = $item[0].id;

      $status = container.target[0].id;

      $("#update").load('/tickets/api/'+$id+'/?status='+$status);

    },

    // set $item relative to cursor position
    onDragStart: function ($item, container, _super) {
      var offset = $item.offset(),
          pointer = container.rootGroup.pointer;

      adjustment = {
        left: pointer.left - offset.left,
        top: pointer.top - offset.top
      };

      _super($item, container);
    },
    onDrag: function ($item, position) {
      $item.css({
        left: position.left - adjustment.left,
        top: position.top - adjustment.top
      });
    }
  });
  </script>
@endsection
