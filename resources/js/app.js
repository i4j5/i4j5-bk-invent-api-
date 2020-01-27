import '../sass/app.scss'
import './bootstrap'

import DecoupledEditor from '@ckeditor/ckeditor5-build-decoupled-document'
import '@ckeditor/ckeditor5-build-decoupled-document/build/translations/ru.js'


$('oembed[url]').each(  function( index, element )  {
    let $el = $(element)
    let url = $el.attr('url')
    $el.html(`<div style="position: relative; padding-bottom: 100%; height: 0; padding-bottom: 56.2493%;">
                <iframe style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;" src="${url}" frameborder="0" allowfullscreen>
                </iframe>
            </div>`)

})

DecoupledEditor
		.create( document.querySelector( '#editor' ), {
            language: 'ru',
            // toolbar: [ 'heading', '|', 'bold', 'italic', 'link' ]
            mediaEmbed: {
                previewsInData: true,
                removeProviders: [ 'instagram', 'twitter', 'googleMaps', 'flickr', 'facebook' ]
            },
            ckfinder: {
                uploadUrl: '/pages/image-upload',
                options: {
                    resourceType: 'Images',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                }
            }
		} )
		.then( editor => {
			const toolbarContainer = document.querySelector( '.toolbar-container' );

			toolbarContainer.prepend( editor.ui.view.toolbar.element );

		    editor.model.document.on( 'change:data', () => {
                $('#data-editor').val(editor.getData())
            });
		} )
		.catch( err => {
			console.error( err.stack );
        } );
        