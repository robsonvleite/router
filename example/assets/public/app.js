(() => {
  const $ = (sel) => document.querySelector(sel);

  const elCounter = $('#counter');
  const btnInc = $('#incBtn');
  const btnHead = $('#headBtn');

  if (!elCounter || !btnInc || !btnHead) {
    return;
  }

  const getCount = () => parseInt(elCounter.getAttribute('data-count') || '0', 10);
  const setCount = (n) => {
    elCounter.setAttribute('data-count', String(n));
    elCounter.textContent = String(n);
  };

  btnInc.addEventListener('click', () => {
    setCount(getCount() + 1);
  });

  btnHead.addEventListener('click', async () => {
    try {
      const res = await fetch('style.css', { method: 'HEAD' });
      const etag = res.headers.get('ETag');
      const lm = res.headers.get('Last-Modified');
      alert('HEAD /style.css\n' +
            'Status: ' + res.status + '\n' +
            'ETag: ' + (etag || '-') + '\n' +
            'Last-Modified: ' + (lm || '-'));
    } catch (e) {
      console.error(e);
      alert('HEAD request failed');
    }
  });
})();