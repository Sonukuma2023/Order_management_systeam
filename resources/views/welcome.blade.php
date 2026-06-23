<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div id="app"></div>
        <h1>count : {{ count }}</h1>
    <button @click = "increment">Increment</button>
    <button @click ="descrement">Descrement</button>
</body>

<script></script>
<script>
    const { ref } = Vue;
    const app = new vuecreate({
        setup(){
            const count = ref(0);
            const increnent()=>{
                count.value++;
            };
            const increnent()=>{
                count.value--;
            };
            
            return {
                count,
                increment,
                descrement
            }
        }

    });
    app.mount('#app');
    const app = vue.create({
        data(){
            return {
                url: 'https://vuejs.org/images/logo.png',
                count: 0
            }
        }
        methods:{
            increment(){
                this.count++;
            }
        }

    });
</script>
</html>