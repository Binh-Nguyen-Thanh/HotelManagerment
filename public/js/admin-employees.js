document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    
    // Employee card hover effects
    const employeeCards = document.querySelectorAll('.employee-card');
    
    employeeCards.forEach(card => {
        // Add hover delay for smooth transition
        let hoverTimeout;
        
        card.addEventListener('mouseenter', () => {
            hoverTimeout = setTimeout(() => {
                card.classList.add('hover-active');
            }, 100);
        });
        
        card.addEventListener('mouseleave', () => {
            clearTimeout(hoverTimeout);
            card.classList.remove('hover-active');
        });
        
        // Click event for view profile
        const viewBtn = card.querySelector('.btn-view-profile');
        if (viewBtn) {
            viewBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const employeeId = card.dataset.employeeId;
                viewEmployeeProfile(employeeId);
            });
        }
    });
    
    // Action buttons functionality
    document.querySelectorAll('.edit-employee').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const employeeId = this.dataset.id;
            openEditModal(employeeId);
        });
    });
    
    document.querySelectorAll('.delete-employee').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const employeeId = this.dataset.id;
            confirmDeleteEmployee(employeeId);
        });
    });
    
    document.querySelectorAll('.view-employee').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const employeeId = this.dataset.id;
            viewEmployeeProfile(employeeId);
        });
    });
    
    // Create employee button
    document.getElementById('createEmployeeBtn').addEventListener('click', function() {
        openCreateModal();
    });
    
    // Manage positions button
    document.getElementById('managePositionsBtn').addEventListener('click', function() {
        openPositionsModal();
    });
    
    // Functions
    function viewEmployeeProfile(id) {
        console.log('Viewing employee profile:', id);
        // Implement view functionality
        // window.location.href = `/admin/employees/${id}`;
    }
    
    function openEditModal(id) {
        console.log('Opening edit modal for employee:', id);
        // Implement modal opening for edit
    }
    
    function confirmDeleteEmployee(id) {
        if (confirm('Bạn có chắc chắn muốn xóa nhân viên này?')) {
            console.log('Deleting employee:', id);
            // Implement delete functionality
            // fetch(`/admin/employees/${id}`, { method: 'DELETE' })
            //     .then(response => response.json())
            //     .then(data => {
            //         if (data.success) {
            //             window.location.reload();
            //         }
            //     });
        }
    }
    
    function openCreateModal() {
        console.log('Opening create employee modal');
        // Implement modal opening for create
    }
    
    function openPositionsModal() {
        console.log('Opening positions management modal');
        // Implement modal opening for positions
    }
    
    // Smooth scroll for page load
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
    
    // Add animation class to cards for staggered effect
    employeeCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});