import "./File.less";
import React, { useState } from "react";

export default props => {
  const [file, setFile] = useState(null);

  const handleChange = e => {
    const file = e.target.files[0];
    setFile(file);
    props.onChange(file);
  };

  return (
    <div className="File">
      <input onChange={handleChange} type="file" />
      <div className="File__name">{file ? file.name : "Select file"}</div>
    </div>
  );
};
