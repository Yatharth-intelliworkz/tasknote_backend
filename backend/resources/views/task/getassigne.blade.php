<div class="col-sm-6">
  <div class="form-group">
    <label>Select Assignee Members</label>
    {{-- {{dd($show)}}; --}}
    <select class="select2" multiple="multiple" name="members_id[]" id="members_id" data-placeholder="Select a Memebers" style="width: 100%;">
      @if($team->count() > 0)
          <optgroup label="Team" id="teamGroup">
            @foreach($team as $val)
              <option value="team-{{ $val->id }}">{{ $val->name }}</option>
            @endforeach
          </optgroup>
      @endif
      <optgroup label="Members" id="membersGroup">
        @foreach($membersData as $val)
          <option value="members-{{ $val->id }}">{{ $val->name }}</option>
        @endforeach
      </optgroup>
    </select>
    @if ($errors->has('members_id'))
      <span class="text-danger">{{ $errors->first('members_id') }}</span>
    @endif
  </div>
</div>
<script>
  $(function () {
    $('.select2').select2();
  });
  $(document).ready(function () {
        $('#members_id').change(function() {
            if ($('#teamGroup option[value]').is(':selected')) {
                $('#teamGroup').prop('disabled', false);
                $('#membersGroup').prop('disabled', true);
            } else if ($('#membersGroup option[value]').is(':selected')) {
                $('#teamGroup').prop('disabled', true);
                $('#membersGroup').prop('disabled', false);
            }
        });
    });
</script>