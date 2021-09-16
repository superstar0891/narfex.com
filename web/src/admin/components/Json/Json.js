import "./Json.less";

import React from "react";

export default function Json({ body }) {
  return (
    <pre className="Json">{JSON.stringify(JSON.parse(body), null, 2)}</pre>
  );
}
