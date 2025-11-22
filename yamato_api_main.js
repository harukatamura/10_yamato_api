document.getElementById("sendBtn").addEventListener("click", async () => {
  const checked = Array.from(document.querySelectorAll('input[name="order"]:checked'))
    .map(el => el.value);

  if (checked.length === 0) {
    alert("対象データを選択してください。");
    return;
  }

  document.getElementById("result").innerHTML = "<p>処理中...</p>";

  try {
    const res = await fetch("yamato_api_send.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ orders: checked })
    });

    const data = await res.json();

    // 結果を画面に表示
    let html = "<h3>処理結果</h3><ul>";
    data.results.forEach(r => {
      html += `<li>注文番号: ${r.order_no} → ${r.status} ${r.message ? '('+r.message+')' : ''}</li>`;
    });
    html += "</ul>";
    document.getElementById("result").innerHTML = html;

  } catch (err) {
    console.error(err);
    document.getElementById("result").innerHTML = `<p style="color:red;">通信エラーが発生しました</p>`;
  }
});