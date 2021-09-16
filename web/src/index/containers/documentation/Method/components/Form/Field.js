import React from "react";
import { Dropdown, Input, File } from "src/ui";
import * as utils from "../../../../../../utils";
import SVG from "react-inlinesvg";

export default props => {
  const { param, value, onChange } = props;
  if (param.filters.oneOf) {
    return (
      <Dropdown
        value={value}
        onChangeValue={onChange}
        options={param.filters.oneOf.map(value => ({
          title: value,
          value
        }))}
      />
    );
  }

  let type = "text";

  if (param.filters.double || param.filters.positive || param.filters.int) {
    type = "number";
  }

  if (param.name === "ga_code") {
    type = "code";
  }

  if (param.name === "password" || param.name.toUpperCase() === "X_TOKEN") {
    type = "password";
  }

  if (param.name === "object") {
    return <File onChange={onChange} />;
  }

  const placeholder =
    param.name === "ga_code" && utils.getLang("site__authModalGAPlaceholder");
  const indicator = param.name === "ga_code" && (
    <SVG src={require("src/asset/google_auth.svg")} />
  );
  return (
    <Input
      type={type}
      placeholder={placeholder}
      indicator={indicator}
      value={value}
      onTextChange={onChange}
    />
  );
};
