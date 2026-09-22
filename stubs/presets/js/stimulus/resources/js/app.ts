import { Application } from '@hotwired/stimulus';
import HelloController from './controllers/hello-controller';

const application = Application.start();

application.register('hello', HelloController);
