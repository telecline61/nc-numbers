<!-- Display the form using the current value. -->
<div class="numbers-wrapper" style="margin:0; padding-top:0;text-align:left">
    <label for="nc_number" style="cursor: inherit;">
        <strong><p>Add Number</p></strong>
        <p>Add your number here. You can then call this number anywhere in the site with a shortcode. Numbers can be in any format e.g. $123.00, 12,345 12345 etc.</p>
        <p>Code for this number: <span>[nc_number id="<?php echo $myId; ?>"]</span></p>
    </label>
    <input type="text" id="" class="" name="nc_number" value="<?php echo $myValue; ?>" placeholder="" style="width: 100%;margin:0;height: 28px;">
</div>
