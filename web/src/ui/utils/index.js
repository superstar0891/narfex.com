export function noExponents(number) {
  let data = String(number).split(/[eE]/);
  if (data.length === 1) return data[0];

  let z = "",
    sign = this < 0 ? "-" : "",
    str = data[0].replace(".", ""),
    mag = Number(data[1]) + 1;

  if (mag < 0) {
    z = sign + "0.";
    while (mag++) z += "0";
    return z + str.replace(/^\-/, "");
  }
  mag -= str.length;
  while (mag--) z += "0";
  return str + z;
}

export function classNames() {
  let result = [];

  [].concat(Array.prototype.slice.call(arguments)).forEach(function(item) {
    if (!item) {
      return;
    }
    switch (typeof item === "undefined" ? "undefined" : typeof item) {
      case "string":
        result.push(item);
        break;
      case "object":
        Object.keys(item).forEach(function(key) {
          if (item[key]) {
            result.push(key);
          }
        });
        break;
      default:
        result.push("" + item);
    }
  });

  return result.join(" ");
}

export function getScrollbarWidth() {
  const outer = document.createElement("div");
  outer.style.visibility = "hidden";
  outer.style.width = "100px";
  document.body.appendChild(outer);
  const widthNoScroll = outer.offsetWidth;
  outer.style.overflow = "scroll";
  const inner = document.createElement("div");
  inner.style.width = "100%";
  outer.appendChild(inner);
  const widthWithScroll = inner.offsetWidth;
  outer.parentNode.removeChild(outer);
  return widthNoScroll - widthWithScroll;
}

export function parseMd(md) {
  // \n
  md = md.replace(/(\\n)/g, "\n");

  //h
  md = md.replace(/[\#]{6}(.+)/g, "<h6>$1</h6>"); // eslint-disable-line
  md = md.replace(/[\#]{5}(.+)/g, "<h5>$1</h5>"); // eslint-disable-line
  md = md.replace(/[\#]{4}(.+)/g, "<h4>$1</h4>"); // eslint-disable-line
  md = md.replace(/[\#]{3}(.+)/g, "<h3>$1</h3>"); // eslint-disable-line
  md = md.replace(/[\#]{2}(.+)/g, "<h2>$1</h2>"); // eslint-disable-line
  md = md.replace(/[\#]{1}(.+)/g, "<h1>$1</h1>"); // eslint-disable-line

  //links
  md = md.replace(
    /[\[]{1}([^\]]+)[\]]{1}[\(]{1}([^\)\"]+)(\"(.+)\")?[\)]{1}/g,
    '<a href="$2" title="$4">$1</a>'
  ); // eslint-disable-line

  //font styles
  md = md.replace(/[\*\_]{2}([^\*\_]+)[\*\_]{2}/g, "<b>$1</b>"); // eslint-disable-line
  md = md.replace(/[\*\_]{1}([^\*\_]+)[\*\_]{1}/g, "<i>$1</i>"); // eslint-disable-line
  md = md.replace(/[\~]{2}([^\~]+)[\~]{2}/g, "<del>$1</del>"); // eslint-disable-line

  //p
  md = md.replace(/^\s*(\n)?(.+)/gm, function(m) {
    return /\<(\/)?(h\d|ul|ol|li|blockquote|pre|img)/.test(m)
      ? m
      : "<p>" + m + "</p>"; // eslint-disable-line
  });
  return md;
}

export function isFiat(currency) {
  return ["usd", "eur", "rub", "idr", "cny"].includes(currency.toLowerCase());
}
