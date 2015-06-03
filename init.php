<?php
/*
Plugin Name: Ekouk Filebase owner switch
 *  * Plugin URI:   http://ekouk.com
 * Description:  This is an add-on to wp-filebase to change the owner of a file. 
 * Author:       Katie Lacy
 * Author URI:   http://ekouk.com
 *
 * Version:      1.2
*/

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Filebase_Owners_Table extends WP_List_Table {  
    function __construct(){
    global $status, $page;
        parent::__construct( array(
            'singular'  => __( 'fileOwner', 'mylisttable' ),     //singular name of the listed records
            'plural'    => __( 'fileOwners', 'mylisttable' ),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?

    ) );

    add_action( 'admin_head', array( &$this, 'admin_header' ) );            

    }
  function admin_header() {
  ?>
<script type="text/javascript">
    jQuery(document).ready(function($){       
        $('#editOwner').click(function(){
            var checked = []
            var ID = $('#author').val();  
            var OwnerName = $('#author option:selected').text();           
            if ($(".check-column input:checkbox:checked").length > 0){              
                // any one is checked
              $('#notChecked').fadeOut();
              $('.check-column input[type=checkbox]').each(function(){
                if ($(this).prop('checked')) {
                var check = $(this).val();
                    if (check != 'on') { 
                        checked.push(check); 
                    }
                }
                });                
              var arrayLength = checked.length;            
                     $.ajax({
                    type: "POST",
                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                    data: 'action=edit_owner&fileID='+checked+'&owner='+ID,
                    cache: false,
                    success: function (result) {
                        console.log(result);
                       if (result == 'true0') {                              
                            checked.forEach(function(checkedID) {
                                 $('#the-list tr th.check-column input[type=checkbox]').each(function(){
                                     if ($(this).val() === checkedID) {
                                         var prevName = $(this).parent('.check-column').parent().find('td.column-owner').text();                                        
                                          $(this).parent('.check-column').parent().find('td.column-owner').text(OwnerName);
                                     }                               
                                 });     
                            });
                            $('.check-column input[type=checkbox]:checked').each(function(){
                              $(this).removeAttr('checked');
                            });                             
                        $('.files').text(arrayLength);
                        $('#successFiles').fadeIn();                        
                       }
                       else if (result == 'false0') {
                        $('#something').fadeIn();                           
                       }
                       
                    }
                });
                return false;     
            }
            else
            {
             // $('#notChecked').fadeIn();
              
            }
             
            });
        });
</script>
<?php
  
    $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
    if( 'my_list_test' != $page )
    return;
    echo '<style type="text/css">';
    echo '.wp-list-table .column-id { width: 5%; }';
    echo '.wp-list-table .column-filetitle { width: 40%; }';
    echo '.wp-list-table .column-owner { width: 35%; }';
    echo '.wp-list-table .column-fileID { width: 20%;}';
    echo '</style>';  
  }

  function no_items() {
    _e( 'Sorry, No files found.' );
  }

  function column_default( $item, $column_name ) {
    switch( $column_name ) { 
        case 'filetitle':
        case 'owner':  
        case 'dateAdded': 
        case 'category':    

            return $item[ $column_name ];            
        default:
            return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
    }
  }
function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'filetitle' => __( 'File Title', 'mylisttable' ),
            'owner'    => __( 'Owner', 'mylisttable' ),
            'dateAdded'      => __( 'Date Added', 'mylisttable' ),
            'category'      => __( 'Category', 'mylisttable' ),


        );
         return $columns;
    }
function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="fileID[]" value="%s" />', $item['ID']
        );    
    }
    
function get_sortable_columns() {
  $sortable_columns = array(
    'filetitle'  => array('filetitle',false),
    'owner' => array('owner',false),
    'dateAdded'   => array('dateAdded',false),
    'category'   => array('category',false)


  );
  return $sortable_columns;
}


function usort_reorder( $a, $b ) {
  // If no sort, default to title
  $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'dateAdded';
  // If no order, default to asc
  $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'desc';
  // Determine sort order
  $result = strcmp( $a[$orderby], $b[$orderby] );
  // Send final sort direction to usort
  return ( $order === 'asc' ) ? $result : -$result;
}


function prepare_items() {
    global $wpdb;
    $prefix = $wpdb->base_prefix;
  $columns  = $this->get_columns();
  $hidden   = array();  
  $fileItems = array();  
    $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;
    $do_search = ( $search ) ? $wpdb->prepare(" AND file_name LIKE '%%%s%%' ", $search ) : ''; 
 
  
   $data = $wpdb->get_results("
            SELECT file_added_by as owner , file_name as name , file_id as ID , display_name  , file_date as date_added , file_category_name as cat , file_category , cat_parent as parent FROM ".$prefix."wpfb_files fb 
            INNER JOIN ".$prefix."users u
            ON  fb.file_added_by=u.ID
            INNER JOIN ".$prefix."wpfb_cats fbc
            ON  fb.file_category=fbc.cat_id
           $do_search
          ");  
   
   $wpdbCats = array();
    global $wpdb;              
  foreach ($data as $file => $fileItem) :
      
         $fbcats = $wpdb->get_results( "SELECT cat_id as ID, cat_name as name , cat_parent as parent FROM `".$prefix."wpfb_cats` fb WHERE fb.cat_id=".$fileItem->parent);  
         $cats = array();
         foreach ($fbcats as $cat) : 
             $cats[] = $cat->name;
         endforeach;
         $parent = implode('' , $cats);
         $parentCat = ($parent ? $parent.' > ' : '');      
         
         
      $fileItems[] =   
          array( 
            'ID' => $fileItem->ID,
            'filetitle' => $fileItem->name,
            'owner' => $fileItem->display_name, 
            'dateAdded' =>  $fileItem->date_added,
            'category' =>   $parentCat.$fileItem->cat
             );             
  endforeach;   
  $sortable = $this->get_sortable_columns();
  $this->_column_headers = array( $columns, $hidden, $sortable );
     // var_dump($fileItems);

  usort( $fileItems, array( &$this, 'usort_reorder' ) );
 
    
    
  $per_page = 5;
  $current_page = $this->get_pagenum();
  $total_items = count($fileItems);

  // only ncessary because we have sample data
  //$this->found_data = $fileItems;

   //  var_dump($this->found_data);
//  $this->set_pagination_args( array(
//    'total_items' => $total_items,                  //WE have to calculate the total number of items
//    'per_page'    => $per_page                     //WE have to determine how many items to show on a page
//  ) );
  $this->items = $fileItems;
}
} //class



function my_add_menu_items(){
  $hook = add_menu_page( 'File Owners', 'File Owners', 'activate_plugins', 'files', 'my_render_list_page' );
  add_action( "load-$hook", 'add_options' );
}

function add_options() {
  global $myListTable;
  $option = 'per_page';
  
  add_screen_option( $option, $args );
  $myListTable = new Filebase_Owners_Table();
}
add_action( 'admin_menu', 'my_add_menu_items' );
function my_render_list_page(){
  global $myListTable;
  echo '</pre><div class="wrap"><h2>Change File owners</h2>'; 
  $myListTable->prepare_items(); 
?>
<div class="error hidden" id="notChecked"> <p>Whoops, you need to check some files first!</p></div>
<div class="updated hidden" id="successFiles"> <p><span class="files"></span> Files have been updated.</p></div>
<div class="error hidden" id="something"> <p>Whoops, something went wrong please try again or contact <a href="http://support.ekouk.com" target="_blank">ekouk</a>.</p></div>


  <form method="post">
    <input type="hidden" name="files" value="ttest_list_table">  
    <?php 
    $blogusers = get_users( 'orderby=nicename&role=subscriber' );
    $subcribers = array();
    // Array of WP_User objects.
    foreach ( $blogusers as $user ) {       
        $subcribers[] = $user->ID;         
    }
    wp_dropdown_users(array('name' => 'author' , 'exclude' => $subcribers , 'show' => 'user_login' , 'orderby' => 'user_login'));
    ?>
    <input name="update" type="submit" class="button button-primary button-large" id="editOwner" value="Update">

    <?php
    $myListTable->search_box( 'search', 'search_id' );

  $myListTable->display(); 
  echo '</form></div>'; 
  ?>
    
<?php
}
function edit_owner_callback() {
   global $wpdb , $myListTable;
   $fileID = $_POST['fileID'];    
   $OwnerName = $_POST['owner'];   
   $fileID = explode(',' , $fileID);   
   $prefix = $wpdb->base_prefix;
    foreach ($fileID as $ID) :
    $updateRow = $wpdb->update( 
	$prefix.'wpfb_files', 
	array( 
		'file_added_by' => $OwnerName,	// string		
	), 
	array('file_id' => $ID)	
    );
    if($updateRow === FALSE) {
    //show failure message
       $result = 'false';

    }else{ 
    // show success message
    $result = 'true';
    }
   endforeach;   
   echo $result;
}   
add_action('wp_ajax_edit_owner', 'edit_owner_callback');
add_action('wp_ajax_nopriv_edit_owner', 'edit_owner_callback');
