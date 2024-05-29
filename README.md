
# Web Slurm

An easy-to-use web interface for the Slurm command line tool, for scheduling tasks on HPCs. Supports creating and storing bash scripts, multiple organisation and user support, and sharing of jobs and Slurm scripts within organisations.

This was created as my dissertation project for my Computer Science undergraduate degree at the University of Liverpool.


## Acknowledgements
This project makes use of several libraries, listed here in alphabetical order.

 - [Chonky](https://chonky.io)
 - [Firebase JWT](https://github.com/firebase/php-jwt)
 - [FlySystem](https://github.com/thephpleague/flysystem)
 - [Monaco Editor React](https://github.com/react-monaco-editor/react-monaco-editor)
- [Oauth2 Server](https://oauth2.thephpleague.com/)
- [PHP DI](https://php-di.org/)
- [PSR 7](https://github.com/Nyholm/psr7)
- [PSR HTTP Message Bridge](https://github.com/symfony/psr-http-message-bridge)
- [Radix UI](https://www.radix-ui.com/)
- [Slim](https://www.slimframework.com/docs/v3/objects/router.html)
- [Symfony Cache](https://github.com/symfony/cache)
- [Tanstack Query](https://tanstack.com/query/latest)
- [Tanstack Table](https://tanstack.com/table/latest)
- [Tailwind CSS](https://tailwindcss.com/)
- [TUS PHP](https://github.com/ankitpokhrel/tus-php)
- [TUS Server](https://github.com/SpazzMarticus/TusServer)
- [Uppy](https://uppy.io)
- [Crypto JS](https://github.com/brix/crypto-js)
- [JWT Decode](https://github.com/auth0/jwt-decode)
- [Lucide React](https://lucide.dev/guide/packages/lucide-react)
- [Noty](https://ned.im/noty)
- [React Icons](https://react-icons.github.io)


## Deployment

To deploy this project run

```bash
  npm run build
```

This will build the front-end into the assets folder of the backend code.

If the application is not at the root of your domain (i.e https://www.example.com/app rather than https://www.example.com) you may need to update the URLs in the automatically generated index.js to use relative paths.


The Backend can then be hosted using the PHP hosting of your choice.

