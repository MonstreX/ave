import { DatabaseTableEditor } from './editor'

document.addEventListener('DOMContentLoaded', () => {
    if (window.dbConfig) {
        window.dbEditor = new DatabaseTableEditor(window.dbConfig)
    }
})
