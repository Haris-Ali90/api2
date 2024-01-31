var appConfig = (function(){

    'use strict';

    var init = function()
    {
        if ( $('.datatable').length ) {
            var dataTable = $('.datatable').DataTable({
                "aoColumnDefs": [{
                    "bSortable": false,
                    "aTargets": appConfig.get( 'dt.aoColumnDefs.aTargets', [ -1 ] )
                }],
                "order": appConfig.get( 'dt.order', [[ 0, 'asc']] ),
                "searching": appConfig.get( 'dt.searching', false )
            });

            appConfig.set('app.dataTable', dataTable)
        }

        if ( jQuery('.yajrabox').length ) {

            var options = {
                order: appConfig.get( 'dt.order', [[ 1, 'asc']] ),
                searching: appConfig.get( 'dt.searching', true ),
                select: appConfig.get( 'dt.select', false ),
                pageLength: appConfig.get('dt.pages', 250),
                autoWidth: appConfig.get('yajrabox.autoWidth', true),
                // lengthMenu: [ 10, 25, 50, 75, 100 ],
                serverSide: true,
                processing: true,
                ajax: {
                    url: appConfig.get( 'yajrabox.ajax' ),
                    data: appConfig.get( 'yajrabox.ajax.data', function(data) {
                        // show loader  on every ajax request
                        console.log(data);
                    }),
                },
                columns: appConfig.get( 'yajrabox.columns' )
            };

            var scrollx_responsive = {
                scrollX: true,   // enables horizontal scrolling,
                scrollCollapse: true,
                /*columnDefs: [
                    { width: '20%', targets:'_all' }
                ],*/
                // "initComplete": function (settings, json) {
                //     $(this).find('.dataTables_scrollHeadInner').css({"width":"100%"});
                // },
                fixedColumns: true,
            };

            if(appConfig.get('yajrabox.scrollx_responsive', false))
            {
                options = { ...options, ...scrollx_responsive}
            }


            var yajraDataTable = jQuery('.yajrabox').DataTable(options).on('xhr.dt', function( e, settings, json, xhr){

                // hide loader

                hideLoader()
                if (xhr.status === 401) {
                    $.fn.dataTable.ext.errMode = 'none';
                    alert('Your session has been timed-out, please log-in again.');
                    location.reload();
                }

            });

            //console.log(appConfig.get( 'yajrabox.ajax' ));
            /*var yajraDataTable = jQuery('.yajrabox').DataTable({
                order: appConfig.get( 'dt.order', [[ 1, 'asc']] ),
                searching: appConfig.get( 'dt.searching', true ),
                select: appConfig.get( 'dt.select', false ),
                pageLength: appConfig.get('dt.pages', 250),
                serverSide: true,
                processing: true,
                ajax: {
                    url: appConfig.get( 'yajrabox.ajax' ),
                    data: appConfig.get( 'yajrabox.ajax.data', function(data) {}),
                },
                columns: appConfig.get( 'yajrabox.columns' )


            }).on('xhr.dt', function( e, settings, json, xhr){
                if (xhr.status === 401) {
                    $.fn.dataTable.ext.errMode = 'none';
                    alert('Your session has been timed-out, please log-in again.');
                    location.reload();
                }
            });*/

            appConfig.set('app.yajraDataTable', yajraDataTable);

            // fn for trigger function on pagination call
            $(document).on('click','.dataTables_paginate .paginate_button',function () {
                // checking the button is not disabled
                if(!$(this).hasClass("disabled"))
                {
                    showLoader();
                }
            });
        }

        if ( $('.cboxImages').length ) {
            $(".cboxImages").colorbox({rel:'cboxImages', maxWidth:"100%", maxHeight:"100%", photo:true});
        }

        if ( $('.select2').length ) {
            $(".select2").select2()
        }

        if ( $('.ckeditor').length ) {
            $('.ckeditor').each(function(i,v) {
                CKEDITOR.replace( $(v).attr('id') || this );
            });
        }

        if ( $('.icon-picker').length ) {
            $('.icon-picker').qlIconPicker({
                'size': 'large',
                'classes': {
                    'launcher': 'btn btn-primary',
                    'clear': 'remove-times',
                }
            })
        }

        if ( $('.date-range-picker').length ) {
            var dateRangePickerArray = [];
            $('.date-range-picker').each(function(i,v) {
                var selector = $(v).attr('id') ? '#'+$(v).attr('id') : '.date-range-picker';
                dateRangePickerArray[ selector ] = $(selector).daterangepicker()
            });

            appConfig.set('app.daterangepicker', dateRangePickerArray)
        }

        $(document).on('keyup', '.numberOnly', function() {
            var val = $(this).val();
            if ( val.match(/[^\d]+/) ) {
                $(this).val( $(this).val().replace(/[^\d]+/, '') );
            }
        })

        if ( $('.chosen').length ) {
            $('.chosen').chosen();
        }


        if ( $('.datepicker').length ) {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd'
            }).on('changeDate', function(e){
                $(this).datepicker('hide');
            });
        }

        $(document).on('click', '.filter-data, .clear-data, .delete-data', function(e) {
            e.preventDefault();
            console.log('here');
            if($(this).hasClass('clear-data')) {
                $('.grid-filter .form-control').val("").trigger("chosen:updated");
                appConfig.get('app.yajraDataTable').draw();
            }

            if($(this).hasClass('filter-data')) {
                appConfig.get('app.yajraDataTable').draw();
            }
        });

        // Add event listener for opening and closing details
        jQuery('.yajrabox tbody').on('click', 'td.details-control', function () {
            var table   = appConfig.get('app.yajraDataTable');
            var tr      = $(this).closest('tr');
            var row     = table.row( tr );

            if ( row.child.isShown() ) {
                // This row is already open - close it
                row.child.hide();
                tr.removeClass('shown');
            }
            else {
                // Open this row
                row.child( format(row.data()) ).show();
                tr.addClass('shown');
            }
        } );

        function format ( d ) {
            // `d` is the original data object for the row
            var html        = '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;"><tr>';
            var addition    = appConfig.get( 'dt.additional');

            jQuery.each(addition, function(key, obj){
                html+=
                    // '<tr>'+
                    '<td class="dt-default-td">' + obj.name + ': </td>'+
                    '<td style="padding-right: 15px">' + d[obj.value]+ '</td>';
                // '</tr>';
            });
            html+='</tr></table>';

            return html;
        }


        // A-Sync/Lazy execution
        $(document).trigger('appConfig.initialized', appConfig);
    }

    return {
        set: function(key, value) {
            this[ key ] = value
        },

        get: function(key, defaultValue) {
            return this.hasOwnProperty(key) ? this[ key ] : defaultValue
            // return this[ key ] || defaultValue
        },

        init: function(){init()}
    }
})();

$(function () {
    appConfig.init();
});



