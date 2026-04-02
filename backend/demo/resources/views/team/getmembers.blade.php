<div class="col-sm-6">
    <div class="form-group">
      <label>Select Assignee Members</label>
      <select class="select2 editSelect" multiple="multiple" name="members_id[]" id="members_id" data-placeholder="Select a Memebers" style="width: 100%;">
        @foreach($membersList as $val)
          <option value="{{ $val->id }}">{{ $val->name }}</option>
        @endforeach
      </select>
      @if ($errors->has('members_id'))
        <span class="text-danger">{{ $errors->first('members_id') }}</span>
      @endif
    </div>
  </div>
<script>
  $(function () {
    $('.editSelect').select2();
  });
  </script>