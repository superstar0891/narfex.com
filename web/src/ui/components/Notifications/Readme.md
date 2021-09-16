```js
import { Notification } from "./Notifications";

<div style={{ background: "#eee", padding: "1em" }}>
  <Notification
    icon="https://unsplash.it/200/200?random=9"
    unread={true}
    actions={[{ text: "Accept" }, { text: "Cancel", type: "secondary" }]}
    onAction={console.log}
    message="Message"
    markText={`**MarkdownText** example text
example new line [link](link)`}
    date="12 Apr 2030"
  />
</div>;
```

```js
import Notifications from "./Notifications";

<Notifications inline visible={true} emptyText="Нет сообщений" />;
```

```js
import Notifications from "./Notifications";

<Notifications inline pending={true} visible={true} />;
```

```js
import Notifications, { Notification } from "./Notifications";

<Notifications inline visible={true}>
  <Notification
    icon="https://unsplash.it/200/200?random=0"
    unread={true}
    actions={[{ text: "Accept" }, { text: "Cancel", type: "secondary" }]}
    onAction={console.log}
    message="Message"
    date="12 Apr 2030"
  />
  <Notification
    markText="***markText***"
    icon="https://unsplash.it/200/200?random=1"
    unread={true}
    actions={[{ text: "Delete", type: "negative" }]}
    onAction={console.log}
    message="Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s"
    date="12 Apr 2030"
  />
  <Notification
    icon="https://unsplash.it/200/200?random=4"
    unread={false}
    onAction={console.log}
    message="Unread false"
    date="12 Apr 2030"
  />
  <Notification
    icon="https://unsplash.it/200/200?random=5"
    unread={false}
    onAction={console.log}
    message="Lorem Ipsum is simply dummy text of the printing Lorem Ipsum is simply dummy text of the printing"
    date="12 Apr 2030"
  />
  <Notification
    icon="https://unsplash.it/200/200?random=6"
    unread={false}
    onAction={console.log}
    message="Lorem Ipsum is simply dummy text of the printing"
    date="12 Apr 2030"
  />
  <Notification
    icon="https://unsplash.it/200/200?random=7"
    unread={false}
    onAction={console.log}
    message="Message"
    date="12 Apr 2030"
  />
  <Notification
    icon="https://unsplash.it/200/200?random=8"
    unread={false}
    onAction={console.log}
    message="Lorem Ipsum is simply"
    date="12 Apr 2030"
  />
  <Notification
    icon="https://unsplash.it/200/200?random=9"
    unread={false}
    onAction={console.log}
    message="Message"
    date="12 Apr 2030"
  />
  <Notification
    markText="markText"
    icon="https://unsplash.it/200/200?random=10"
    unread={false}
    onAction={console.log}
    message="Lorem Ipsum is simply dummy text of the printing Lorem Ipsum is simply dummy text of the printing"
    date="12 Apr 2030"
  />
</Notifications>;
```
