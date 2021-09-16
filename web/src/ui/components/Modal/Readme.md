```js
import Modal, { ModalHeader } from "./Modal.js";
import Button from "../Button/Button.js";
import { useState } from "react";

const [isOpen, setState] = useState(false);

<div>
  <Button type="small" onClick={() => setState(true)}>
    Open Modal
  </Button>
  <Modal
    className="GAConfirmModal"
    isOpen={isOpen}
    onClose={() => setState(false)}
  >
    <ModalHeader>Modal Header</ModalHeader>
    <div>13123123</div>
  </Modal>
</div>;
```
