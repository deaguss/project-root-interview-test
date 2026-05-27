describe('Task Management Flow', () => {
  it('Logs in, creates a task, and deletes it', () => {
    cy.visit('/login');
    
    cy.get('input[type="email"]').type('admin@taskmanager.com');
    cy.get('input[type="password"]').type('password');
    cy.get('button[type="submit"]').click();
    
    cy.url().should('eq', Cypress.config().baseUrl + '/');
    cy.contains('Task Dashboard').should('be.visible');
    
    cy.contains('+ New Task').click();
    cy.get('input[type="text"]').type('Cypress Automated Task');
    cy.get('textarea').type('Testing task creation via Cypress');
    cy.get('button[type="submit"]').contains('Save').click();
    
    cy.contains('Task created').should('be.visible');
    cy.contains('Cypress Automated Task').should('be.visible');
    
    cy.contains('td', 'Cypress Automated Task')
      .parent()
      .find('button')
      .contains('Delete')
      .click();
      
    cy.contains('Task deleted successfully').should('be.visible');
    cy.contains('Cypress Automated Task').should('not.exist');
  });
});
