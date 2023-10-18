import { Plugin } from 'ckeditor5/src/core';
import sanitizeHtml from 'sanitize-html';

export default class PlainPaste extends Plugin {

  static get requires() {
    return [];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'PlainPaste';
  }

  /**
   * @inheritdoc
   */
  init() {
    const sanitizeOptions = {
      allowedTags: [
        'p',
        'a',
        'h2',
        'h3',
        'h4',
        'h5',
        'ul',
        'ol',
        'li',
        'table',
        'thead',
        'tbody',
        'th',
        'tr',
        'td',
        'b',
        'i',
        'strong',
        'em',
        'dl',
        'dt',
        'dd',
        'blockquote',
        'code',
      ],
      allowedAttributes: { a: ['href'] },
    };

    const editor = this.editor;
    const document = editor.editing.view.document;

    editor.plugins.get('ClipboardPipeline').on('inputTransformation', (event, data) => {
      const html = data.dataTransfer.getData('text/html');
      // Fall back to the plain text but keep the line breaks a <p> tags.
      const plain = ('<p>' + data.dataTransfer.getData('text/plain').replace(/(?:\r\n|\r|\n)/g, '</p><p>') + '</p>')
        .replace(/<p><\/p>/, '');
      const contents = html.length > 0 ? html : plain;

      const cleanHtml = sanitizeHtml(contents, sanitizeOptions);

      data.content = this.editor.data.htmlProcessor.toView(cleanHtml.replace(/<p><\/p>/g, ''));

      // Act like we've already transfored the input. This prevrents the
      // paste-from-office plugin from doing more stuff that basically reverts
      // the work above.
      data._isTransformedWithPasteFromOffice = true;
    }, { priority: 'highest' });
  }

}
