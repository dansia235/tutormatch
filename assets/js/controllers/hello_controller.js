import { Controller } from '@hotwired/stimulus';

/**
 * Hello controller for a quick demo
 */
export default class extends Controller {
  static targets = ['output'];
  static values = {
    name: String
  };
  
  connect() {
    if (this.hasOutputTarget) {
      this.outputTarget.textContent = 'Controller connected!';
    }
  }
  
  greet() {
    const name = this.hasNameValue ? this.nameValue : 'World';
    
    if (this.hasOutputTarget) {
      this.outputTarget.textContent = `Hello, ${name}!`;
    }
  }
}
