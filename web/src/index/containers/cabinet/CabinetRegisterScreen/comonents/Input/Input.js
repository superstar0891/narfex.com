import "./input.less";

import React, { useState } from "react";
import { useSelector } from "react-redux";
import { Input, Tooltip } from "src/ui";
import { classNames as cn } from "src/utils";
import { adaptiveSelector } from "src/selectors";

export default props => {
  const adaptive = useSelector(adaptiveSelector);
  const [focus, setFocus] = useState(false);

  return (
    <Tooltip
      active={focus || (adaptive ? focus && props.error : props.error)}
      place={adaptive ? "top" : "left"}
      disableHover={true}
      className={cn("RegistrationInputTooltip", { error: props.error })}
      title={props.title}
    >
      <Input
        onFocus={() => setFocus(true)}
        onBlur={() => setFocus(false)}
        {...props}
      />
    </Tooltip>
  );
};
