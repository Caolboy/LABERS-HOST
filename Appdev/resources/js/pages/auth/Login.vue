<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import Terms from '@/components/Terms.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, X } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showTermsModal = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const openTermsModal = () => {
    showTermsModal.value = true;
};

const closeTermsModal = () => {
    showTermsModal.value = false;
};
</script>

<template>
    <div class="min-h-screen w-full flex items-center justify-center relative overflow-hidden"
        style="background: linear-gradient(135deg, rgba(253, 124, 33, 0.9) 0%, rgba(255, 157, 0, 0.9) 100%);">
    <div class="relative z-10 w-[98vw] min-h-[93vh] mx-4 my-4 md:mx-8 md:my-8 rounded-2xl shadow-2xl flex flex-col md:flex-row overflow-hidden p-2 md:p-8 bg-[#F9F4EB]">
        <div class="absolute left-1/2 -translate-x-1/2 top-6 md:left-6 md:translate-x-0 md:top-6">
            <div class="border-2 border-[#FD7C21] text-[#FD7C21] rounded-lg p-2 px-8 inline-block bg-transparent">
                <span class="font-bold">LABERS</span>
            </div>
        </div>
        <div class="absolute left-1/2 -translate-x-1/2 bottom-6 md:left-6 md:translate-x-0 md:bottom-6 text-xs text-[#4E413B] whitespace-nowrap">
            By logging in, you agree to our 
            <button 
                @click="openTermsModal" 
                class="text-[#FD7C21] hover:underline cursor-pointer bg-transparent border-none p-0 font-inherit"
            >
                Terms and Conditions
            </button>.
        </div>
        <div class="flex--1 flex flex-col justify-center px-4 py-8 md:py-0 md:px-12 lg:px-20 pt-20">
            <div class="max-w-md w-full mx-auto">
                <h1 class="font-semibold text-[#FD7C21] mb-2 text-center text-[2.75rem]">Welcome back!</h1>
                <p class="text-[#4E413B] mb-12 text-center text-sm">Please enter your account details to login.</p>
                <Head title="Log in" />
                <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
                    {{ status }}
                </div>
                <form @submit.prevent="submit" class="flex flex-col">
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                        <Label for="email" class="text-[#4E413B] font-semibold mb-2 block text-base">Domain Email</Label>
                        </div>
                        <Input
                            id="email"
                            type="text"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="e.g. 202310838@gordoncollege.edu.ph"
                            class="text-base border border-white/30 focus:border-white focus:ring-1 focus:ring-white placeholder:text-gray-400 placeholder:text-sm h-10 text-[#4E413B] bg-white"
                        />
                        <InputError :message="form.errors.email" class="text-[#FD7C21] mt-2" />
                    </div>
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <Label for="password" class="text-[#4E413B] font-semibold text-base">Password</Label>
                        </div>
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            v-model="form.password"
                            placeholder="Enter your password"
                            class="text-base border border-white/30 focus:border-white focus:ring-1 focus:ring-white placeholder:text-gray-400 placeholder:text-sm h-10 bg-white"
                        />
                        <InputError :message="form.errors.password" class="text-[#FD7C21] mt-2" />
                    </div>
                    <!-- Stay signed in -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <div class="flex items-center gap-2">
                            <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                            <label for="remember" class="text-sm text-[#4E413B]">Stay signed in</label>
                        </div>
                        <!-- Forgot password link -->
                        <TextLink
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs text-[#FD7C21] !text-[#FD7C21] !no-underline"
                            :tabindex="5"
                        >
                            Forgot password?
                        </TextLink>
                        </div>
                    <Button type="submit" class="w-full h-10 bg-gradient-to-r from-[#FD7C21] to-[#FF9D00] text-white shadow-md hover:from-[#FD7C21]/90 hover:to-[#FF9D00]/90 mt-6" :tabindex="4" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Login
                    </Button>
                    <div class="text-center text-sm text-[#4E413B] mt-5">
                        Don't have an account?
                        <TextLink :href="route('register')" :tabindex="5" class="text-[#FD7C21] !text-[#FD7C21] !no-underline">Register</TextLink>
                    </div>
                </form>
            </div>
        </div>
    <div class="hidden md:flex flex-1 items-center justify-center bg-transparent p-0 m-0 ml-13 relative">
        <div class="relative w-5xl h-5xl">
            <img src="/comlab.jpg" alt="Gordon College" class="object-cover w-full h-full rounded-2xl shadow-xl" />
            <div class="absolute inset-0 bg-gradient-to-br from-orange-600 to-orange-400 opacity-70 rounded-2xl"></div>
        </div>
        <img src="/labers1.png" alt="Gordon College Lab" class="w-xl h-xl absolute rounded-2xl shadow-xl z-10" />
    </div>
    </div>

    <div v-if="showTermsModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div 
            class="absolute inset-0 bg-black/50" 
            @click="closeTermsModal"
        ></div>
        
        <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-[#F9F4EB]">
                <h2 class="text-xl font-semibold text-[#4E413B]">Terms and Conditions</h2>
                <button 
                    @click="closeTermsModal"
                    class="p-2 hover:bg-gray-100 rounded-full transition-colors"
                >
                    <X class="h-5 w-5 text-gray-500" />
                </button>
            </div>
            
            <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
                <Terms />
            </div>
            
            <div class="flex justify-end gap-3 p-6 border-t border-gray-200 bg-[#F9F4EB]">
                <Button 
                    @click="closeTermsModal"
                    variant="outline"
                    class="px-6"
                >
                    Close
                </Button>
            </div>
        </div>
    </div>
</div>
</template>