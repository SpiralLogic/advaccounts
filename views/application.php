<table class='width100'>
  <tr>
    <td class='menu_group top'>
      <table class='width100'>
        <tr>
          <td class='menu_group' colspan={{ $colspan }} >
            {{ $name }}
          </td>
        </tr>
        <tr>
          <td class='width50 menu_group_items'>
            <ul>
              @foreach ($lmods as $mod)@
              @if ($mod['label'] == "")@
                <li class='empty'>&nbsp;</li>
                @elseif ($mod['access'])@
                <li>{{ $mod['link'] }} </li>
                @else@
                <li><span class='inactive'>{{ $mod['anchor'] }} </span></li>
                @endif@
              @endforeach@
            </ul>
          </td>
          @if ($rmods)@
  <td class='width50 menu_group_items'>
  <ul>
        @foreach ($rmods as $mod)@
            @if ($mod['label'] == "")@
              <li class='empty'>&nbsp;</li>
              @elseif ($mod['access'])@
              <li>{{ $mod['link'] }} </li>
              @else@
              <li><span class='inactive'>{{ $mod['anchor'] }} </span></li>
              @endif@
            @endforeach@
          @endif@
        </ul></td>

        </tr>
      </table>
    </td>
  </tr>
</table>
