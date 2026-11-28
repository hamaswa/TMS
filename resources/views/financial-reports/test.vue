<template>
    <template v-for="user in users" :key="user.id">
            <li>
                User Name : {{ user.name }} <button @click="showRole(showDetails)">{{showDetails ? "Show Details" : "Hide Details" }}</button>
                Role      : <h3 v-if="showDetails">{{ user.role }}</h3>
                Status    : <h3>
                             <span v-if="user.isActive">Active</span>
                             <span v-else>Suspended</span>
                            </h3>
                <button @method="toggleStatus(user)">Status</button>

            </li>
    </template>
</template>


<script>

export default {
    data() {
        return {
            showDetails: false,
            user: {
                id: 1,
                name: 'Bilal Ahmed',
                role: 'Tenant Admin',
                isActive: true,
            },
        };
    },


    methods: {
        showRole(showDetails){
            this.showDetails = !this.showDetails
        },
        toggleStatus(user){
            user.isActive = !user.isActive
        }
    }
}

</script>



