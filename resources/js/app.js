import '../sass/app.scss'
import './bootstrap'

import DecoupledEditor from '@ckeditor/ckeditor5-build-decoupled-document'
import '@ckeditor/ckeditor5-build-decoupled-document/build/translations/ru.js'

DecoupledEditor
		.create( document.querySelector( '#editor' ), {
            language: 'ru',
			// toolbar: [ 'heading', '|', 'bold', 'italic', 'link' ]
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
        