(function (blocks, element) {
  var el = element.createElement;

  blocks.registerBlockType('easy-auth/login-form', {
    title: 'Easy Auth Login',
    icon: 'lock',
    category: 'widgets',
    description: 'Render the Easy Auth Login form.',
    edit: function () {
      return el('div', { className: 'easy-auth-block-placeholder' }, 'Easy Auth Login form will render on the frontend.');
    },
    save: function () {
      return null;
    }
  });
})(window.wp.blocks, window.wp.element);
