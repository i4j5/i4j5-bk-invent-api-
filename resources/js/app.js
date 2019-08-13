import '../sass/app.scss'
import './bootstrap'


import * as $ from 'jquery'
import ClassicEditor from '@ckeditor/ckeditor5-build-classic'
import '@ckeditor/ckeditor5-build-classic/build/translations/ru.js'

ClassicEditor
    .create(document.querySelector('#editor'), {
        language: 'ru',
        // toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'textColor', 'bulletedList', 'numberedList', '|', 'undo', 'redo' ],
        // table: {
        //     contentToolbar: [ 'tableRow', 'tableColumn', 'mergeTableCells' ],
        //     tableToolbar: [ 'blockQuote' ]
        // },
    }) 