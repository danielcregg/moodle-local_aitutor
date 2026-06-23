// AI Tutor browser glue (plain JS, no AMD build). On a STACK quiz-attempt page it adds a
// "💡 Hint" button to each STACK question, reads the student's current answer + any grader
// feedback from the DOM, and asks the plugin's server-side endpoint for one Socratic hint
// (escalating with each press). The AI key stays on the server.
(function () {
  'use strict';
  var CFG = window.AITUTOR;
  if (!CFG || !CFG.ajaxurl) { return; }
  if (!document.body || document.body.id.indexOf('page-mod-quiz-attempt') !== 0) { return; }

  function questionText(que) {
    var el = que.querySelector('.qtext') || que.querySelector('.formulation');
    return el ? el.innerText.replace(/\s+/g, ' ').trim() : '';
  }
  function currentAnswer(que) {
    var inputs = que.querySelectorAll('.formulation input[type="text"], .formulation textarea');
    var parts = [];
    inputs.forEach(function (i) { if (i.value && i.value.trim()) { parts.push(i.value.trim()); } });
    return parts.join(', ');
  }
  function graderFeedback(que) {
    var el = que.querySelector('.stackprtfeedback') || que.querySelector('.outcome .feedback') || que.querySelector('.feedback');
    return el ? el.innerText.replace(/\s+/g, ' ').trim() : '';
  }

  function attach(que, idx) {
    if (que.querySelector('.aitutor-box')) { return; }
    var state = { attempt: 0 };

    var box = document.createElement('div');
    box.className = 'aitutor-box';
    box.style.cssText = 'margin-top:10px';

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-secondary btn-sm aitutor-btn';
    btn.textContent = '💡 ' + (CFG.label || 'Hint');

    var panel = document.createElement('div');
    panel.className = 'aitutor-hint';
    panel.style.cssText = 'margin-top:8px;padding:10px 12px;border:1px solid #ffe2a8;background:#fff7e6;border-radius:8px;display:none;font-size:14px;line-height:1.5';

    box.appendChild(btn);
    box.appendChild(panel);
    var anchor = que.querySelector('.ablock') || que.querySelector('.formulation') || que;
    anchor.appendChild(box);

    btn.addEventListener('click', function () {
      if (CFG.maxhints && state.attempt >= CFG.maxhints) {
        panel.style.display = 'block';
        panel.textContent = 'You have used all available hints for this question. Give it another try!';
        return;
      }
      state.attempt += 1;
      btn.disabled = true;
      panel.style.display = 'block';
      panel.textContent = 'Tutor is thinking…';
      var body = new URLSearchParams({
        sesskey: CFG.sesskey,
        cmid: String(CFG.cmid || ''),
        question: questionText(que),
        answer: currentAnswer(que),
        feedback: graderFeedback(que),
        attempt: String(state.attempt)
      });
      fetch(CFG.ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
      }).then(function (r) { return r.json(); })
        .then(function (j) {
          panel.textContent = j.hint ? ('💡 ' + j.hint) : ('Tutor unavailable: ' + (j.error || 'unknown error'));
        })
        .catch(function (e) { panel.textContent = 'Tutor error: ' + e.message; })
        .finally(function () { btn.disabled = false; });
    });
  }

  // Phase 3: ask the RL teaching policy what to practise next (based on the student's measured
  // mastery) and show it as a small banner. Optional + isolated — never affects the hint flow.
  function showRecommendation() {
    if (!CFG.recommendurl) { return; }
    var body = new URLSearchParams({ sesskey: CFG.sesskey, cmid: String(CFG.cmid || '') });
    fetch(CFG.recommendurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString()
    }).then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j || !j.skill) { return; }
        var banner = document.createElement('div');
        banner.className = 'aitutor-reco';
        banner.style.cssText = 'margin:10px 0;padding:10px 14px;border:1px solid #cfe2ff;background:#eef5ff;border-radius:8px;font-size:14px';
        // Build with text nodes (never innerHTML) so a hostile response can't inject markup.
        var strong = document.createElement('strong');
        strong.textContent = (CFG.reclabel || 'Practise next') + ':';
        var tail = document.createElement('span');
        tail.style.color = '#667';
        tail.textContent = ' — suggested by the RL teaching policy';
        var mid = ' ' + (j.label || j.skill) + (j.difficulty ? ' (' + j.difficulty + ')' : '');
        banner.appendChild(document.createTextNode('🧭 '));
        banner.appendChild(strong);
        banner.appendChild(document.createTextNode(mid));
        banner.appendChild(tail);
        var anchor = document.querySelector('.que');
        if (anchor && anchor.parentNode) { anchor.parentNode.insertBefore(banner, anchor); }
      })
      .catch(function () { /* recommendation is optional; never disrupt the page */ });
  }

  function init() {
    // Only attach to STACK questions - never send other question types' content to the AI.
    document.querySelectorAll('.que.stack').forEach(attach);
    try { showRecommendation(); } catch (e) { /* optional feature */ }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
