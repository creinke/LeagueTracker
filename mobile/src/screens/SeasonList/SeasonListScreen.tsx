import React, {useEffect, useState} from 'react';
import {View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator} from 'react-native';
import SeasonService from '../../services/SeasonService';
import {Season} from '../../types';

export default function SeasonListScreen({navigation}: any) {
    const [seasons, setSeasons] = useState<Season[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        SeasonService.getSeasons()
            .then(setSeasons)
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return <ActivityIndicator size="large" style={styles.centered}/>;
    }

    return (
        <View style={styles.container}>
            <FlatList
                data={seasons}
                keyExtractor={(item) => item.id.toString()}
                renderItem={({item}) => (
                    <TouchableOpacity
                        style={styles.item}
                        onPress={() => navigation.navigate('SeasonDetail', {id: item.id})}
                    >
                        <Text style={styles.name}>{item.name}</Text>
                        <Text>{item.startDate} to {item.endDate}</Text>
                    </TouchableOpacity>
                )}
            />
        </View>
    );
}

const styles = StyleSheet.create({
    container: {flex: 1, backgroundColor: '#fff'},
    centered: {flex: 1, justifyContent: 'center', alignItems: 'center'},
    item: {padding: 15, borderBottomWidth: 1, borderBottomColor: '#eee'},
    name: {fontSize: 18, fontWeight: 'bold'},
});
