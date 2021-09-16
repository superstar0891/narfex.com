import "./Form.less";
import React from "react";
import { classNames as cn } from "../../../utils/index.js";

const Form = props => (
  <form {...props} className={cn("Form", props.className)}>
    {props.children}
  </form>
);

export default Form;
